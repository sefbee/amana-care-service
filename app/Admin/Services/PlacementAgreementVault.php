<?php

namespace App\Admin\Services;

use Aws\S3\S3Client;
use Aws\Sts\StsClient;
use App\Admin\Models\Placement;
use App\Admin\Models\PlacementAgreementReveal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PlacementAgreementVault
{
    /** @return array{disk:string,key:string,mime:string,size:int,sha256:string} */
    public function store(UploadedFile $file): array
    {
        $vaultDisk = config('caregivers.storage_disk');
        $quarantineDisk = config('caregivers.quarantine_disk');
        $vaultConfig = config("filesystems.disks.{$vaultDisk}", []);
        $quarantineConfig = config("filesystems.disks.{$quarantineDisk}", []);
        if (($vaultConfig['driver'] ?? null) !== 's3' || ($quarantineConfig['driver'] ?? null) !== 's3'
            || empty($vaultConfig['bucket']) || empty($quarantineConfig['bucket'])) {
            throw new RuntimeException('Private agreement vault and quarantine buckets are not configured.');
        }

        $mime = $file->getMimeType() ?: '';
        $extensions = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (! isset($extensions[$mime]) || $file->getSize() > 10 * 1024 * 1024) {
            throw new RuntimeException('Agreement must be a PDF, JPEG, or PNG file no larger than 10 MB.');
        }

        $key = 'placements/agreements/'.Str::uuid().'.'.$extensions[$mime];
        $quarantine = Storage::disk($quarantineDisk);
        $source = fopen($file->getRealPath(), 'rb');
        if (! is_resource($source)) {
            throw new RuntimeException('Unable to read the signed agreement.');
        }
        try {
            $quarantine->put($key, $source);
        } finally {
            fclose($source);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'amanah-agreement-');
        try {
            $source = $quarantine->readStream($key);
            $target = fopen($tempPath, 'wb');
            if (! is_resource($source) || ! is_resource($target)) {
                throw new RuntimeException('Unable to prepare agreement for malware scanning.');
            }
            stream_copy_to_stream($source, $target);
            fclose($source);
            fclose($target);

            $scan = Process::timeout(120)->run([config('caregivers.scanner_binary'), '--no-summary', '--', $tempPath]);
            if (! $scan->successful()) {
                throw new RuntimeException($scan->exitCode() === 1
                    ? 'The signed agreement was rejected by malware scanning.'
                    : 'Agreement malware scanning is unavailable; activation is blocked.');
            }

            $source = $quarantine->readStream($key);
            if (! is_resource($source) || ! Storage::disk($vaultDisk)->put($key, $source)) {
                if (is_resource($source)) fclose($source);
                throw new RuntimeException('Unable to promote the clean agreement into private storage.');
            }
            fclose($source);
            $quarantine->delete($key);

            return [
                'disk' => $vaultDisk,
                'key' => $key,
                'mime' => $mime,
                'size' => (int) $file->getSize(),
                'sha256' => hash_file('sha256', $tempPath),
            ];
        } catch (\Throwable $exception) {
            throw new RuntimeException('Agreement remains unavailable because secure scanning or storage failed.', previous: $exception);
        } finally {
            if (is_file($tempPath)) @unlink($tempPath);
        }
    }

    public function delete(string $disk, string $key): void
    {
        if ($disk === config('caregivers.storage_disk') && str_starts_with($key, 'placements/agreements/')) {
            Storage::disk($disk)->delete($key);
        }
        $quarantineDisk = config('caregivers.quarantine_disk');
        if ($quarantineDisk && str_starts_with($key, 'placements/agreements/')) {
            Storage::disk($quarantineDisk)->delete($key);
        }
    }

    public function revealUrl(Placement $placement, int $actorId, string $reason): string
    {
        $disk = config('caregivers.storage_disk');
        $diskConfig = config("filesystems.disks.{$disk}", []);
        $roleArn = config('caregivers.vault_role_arn');
        if ($placement->agreement_disk !== $disk || ($diskConfig['driver'] ?? null) !== 's3'
            || empty($diskConfig['bucket']) || empty($roleArn)) {
            $this->recordReveal($placement, $actorId, $reason, 'Denied');
            activity('placement_agreement_reveal')->causedBy($actorId)->performedOn($placement)
                ->withProperties(['result' => 'denied', 'reason_length' => mb_strlen($reason)])
                ->log('Placement agreement reveal denied');
            return '';
        }

        try {
            $bucket = $diskConfig['bucket'];
            $key = $placement->agreement_key;
            if (! str_starts_with($key, 'placements/agreements/')) {
                throw new RuntimeException('Agreement object key is outside the allowed prefix.');
            }
            $region = $diskConfig['region'] ?? config('filesystems.disks.s3.region');
            $sts = new StsClient(['version' => 'latest', 'region' => $region]);
            $session = $sts->assumeRole([
                'RoleArn' => $roleArn,
                'RoleSessionName' => 'placement-'.$placement->id.'-user-'.$actorId,
                'DurationSeconds' => 900,
                'SourceIdentity' => (string) $actorId,
                'Policy' => json_encode(['Version' => '2012-10-17', 'Statement' => [[
                    'Effect' => 'Allow', 'Action' => ['s3:GetObject'], 'Resource' => 'arn:aws:s3:::'.$bucket.'/'.$key,
                ]]], JSON_THROW_ON_ERROR),
                'Tags' => [['Key' => 'AmanahActorId', 'Value' => (string) $actorId]],
            ]);

            $credentials = $session['Credentials'];
            $s3 = new S3Client([
                'version' => 'latest', 'region' => $region,
                'credentials' => ['key' => $credentials['AccessKeyId'], 'secret' => $credentials['SecretAccessKey'], 'token' => $credentials['SessionToken']],
            ]);
            $expiresAt = now()->addSeconds(300);
            $command = $s3->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key' => $key,
                'ResponseContentDisposition' => 'attachment; filename="placement-agreement-'.$placement->id.'.'.$this->extensionFor($placement->agreement_mime).'"',
                'ResponseContentType' => $placement->agreement_mime,
            ]);
            $url = (string) $s3->createPresignedRequest($command, $expiresAt)->getUri();
            $this->recordReveal($placement, $actorId, $reason, 'Allowed', $expiresAt);
            activity('placement_agreement_reveal')->causedBy($actorId)->performedOn($placement)
                ->withProperties(['result' => 'allowed', 'reason_length' => mb_strlen($reason), 'expires_at' => $expiresAt->toIso8601String()])
                ->log('Placement agreement reveal URL issued');
            return $url;
        } catch (\Throwable $exception) {
            $this->recordReveal($placement, $actorId, $reason, 'Failed');
            activity('placement_agreement_reveal')->causedBy($actorId)->performedOn($placement)
                ->withProperties(['result' => 'failed', 'reason_length' => mb_strlen($reason)])
                ->log('Placement agreement reveal failed');
            throw new RuntimeException('Unable to issue a private placement agreement URL.', previous: $exception);
        }
    }

    private function recordReveal(Placement $placement, int $actorId, string $reason, string $result, $expiresAt = null): void
    {
        PlacementAgreementReveal::query()->create([
            'placement_id' => $placement->id,
            'actor_id' => $actorId,
            'reason' => trim($reason),
            'attempted_at' => now(),
            'expires_at' => $expiresAt,
            'result' => $result,
            'ip_address' => request()->ip(),
        ]);
    }

    private function extensionFor(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => 'bin',
        };
    }
}
