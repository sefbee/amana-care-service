<?php

namespace App\Admin\Services;

use App\Admin\Models\Caregiver;
use App\Admin\Models\CaregiverDocument;
use App\Admin\Models\CaregiverDocumentReveal;
use Aws\S3\S3Client;
use Aws\Sts\StsClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CaregiverDocumentVault
{
    public function upload(Caregiver $caregiver, UploadedFile $file, string $type, int $actorId): CaregiverDocument
    {
        $vaultDisk = config('caregivers.storage_disk');
        $quarantineDisk = config('caregivers.quarantine_disk');
        $vaultConfig = config("filesystems.disks.{$vaultDisk}", []);
        $quarantineConfig = config("filesystems.disks.{$quarantineDisk}", []);
        if (($vaultConfig['driver'] ?? null) !== 's3' || ($quarantineConfig['driver'] ?? null) !== 's3'
            || empty($vaultConfig['bucket']) || empty($quarantineConfig['bucket'])) {
            throw new RuntimeException('Private S3 vault and quarantine buckets are not configured.');
        }

        $extensions = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
        $mime = $file->getMimeType() ?: '';
        if (! isset($extensions[$mime])) {
            throw new RuntimeException('Only PDF, JPEG, and PNG evidence files are accepted.');
        }
        $key = 'caregivers/'.$caregiver->getKey().'/'.Str::uuid().'.'.$extensions[$mime];
        $quarantine = Storage::disk($quarantineDisk);
        $uploadStream = fopen($file->getRealPath(), 'rb');
        if (! is_resource($uploadStream)) {
            throw new RuntimeException('Unable to read the uploaded document.');
        }
        try {
            if (! $quarantine->put($key, $uploadStream)) {
                throw new RuntimeException('Unable to store the document in quarantine.');
            }
        } finally {
            fclose($uploadStream);
        }

        $document = $caregiver->documents()->create([
            'type' => $type,
            'storage_disk' => $quarantineDisk,
            'storage_key' => $key,
            'original_name' => basename($file->getClientOriginalName()),
            'mime_type' => $mime,
            'size_bytes' => (int) $file->getSize(),
            'expiry_date' => request()->date('expiry_date')?->toDateString(),
            'scan_status' => 'Quarantined',
            'uploaded_by' => $actorId,
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'amanah-scan-');
        try {
            $source = $quarantine->readStream($key);
            if (! is_resource($source)) {
                throw new RuntimeException('Unable to prepare quarantined file for malware scanning.');
            }
            $target = fopen($tempPath, 'wb');
            if (! is_resource($target)) {
                fclose($source);
                throw new RuntimeException('Unable to prepare quarantined file for malware scanning.');
            }
            try {
                $copiedBytes = stream_copy_to_stream($source, $target);
                if ($copiedBytes === false || $copiedBytes !== (int) $file->getSize()) {
                    throw new RuntimeException('The full quarantined document could not be staged for malware scanning.');
                }
            } finally {
                fclose($source);
                fclose($target);
            }

            $scan = Process::timeout(120)->run([config('caregivers.scanner_binary'), '--no-summary', '--', $tempPath]);
            if ($scan->exitCode() === 1) {
                $document->forceFill(['scan_status' => 'Rejected', 'scan_result' => 'malware_detected', 'scanned_at' => now()])->save();
                return $document;
            }
            if (! $scan->successful()) {
                $document->forceFill(['scan_status' => 'Failed', 'scan_result' => 'scanner_error', 'scanned_at' => now()])->save();
                return $document;
            }

            $source = $quarantine->readStream($key);
            if (! is_resource($source)) {
                throw new RuntimeException('Unable to read the clean document from quarantine for promotion.');
            }
            try {
                if (! Storage::disk($vaultDisk)->put($key, $source)) {
                    throw new RuntimeException('Unable to promote the clean document to the private vault.');
                }
            } finally {
                fclose($source);
            }

            $document->forceFill([
                'storage_disk' => $vaultDisk,
                'scan_status' => 'Clean',
                'scan_result' => 'clean',
                'scanned_at' => now(),
            ])->save();
            if (! $quarantine->delete($key)) {
                Storage::disk($vaultDisk)->delete($key);
                throw new RuntimeException('Unable to remove the quarantined copy after promotion.');
            }

            return $document;
        } catch (\Throwable $exception) {
            $document->forceFill(['scan_status' => 'Failed', 'scan_result' => 'scan_or_promotion_error', 'scanned_at' => now()])->save();
            throw new RuntimeException('Document remains blocked in quarantine because scanning or promotion failed.', previous: $exception);
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function revealUrl(CaregiverDocument $document, int $actorId, string $reasonCode, bool $allowed): string
    {
        if (! $allowed || $document->scan_status !== 'Clean' || $document->storage_disk !== config('caregivers.storage_disk')) {
            $this->recordReveal($document, $actorId, $reasonCode, 'Denied');
            activity('caregiver_document_reveal')->causedBy($actorId)->performedOn($document)->withProperties(['reason_code' => $reasonCode, 'result' => 'denied'])->log('Caregiver document reveal denied');
            return '';
        }

        $diskConfig = config('filesystems.disks.'.config('caregivers.storage_disk'), []);
        $roleArn = config('caregivers.vault_role_arn');
        if (($diskConfig['driver'] ?? null) !== 's3' || empty($diskConfig['bucket']) || empty($roleArn)) {
            $this->recordReveal($document, $actorId, $reasonCode, 'Denied');
            activity('caregiver_document_reveal')->causedBy($actorId)->performedOn($document)->withProperties(['reason_code' => $reasonCode, 'result' => 'denied', 'reason' => 'vault_not_configured'])->log('Caregiver document reveal denied');
            return '';
        }

        try {
            $bucket = $diskConfig['bucket'];
            $key = $document->storage_key;
            $region = $diskConfig['region'] ?? config('filesystems.disks.s3.region');
            $sts = new StsClient(['version' => 'latest', 'region' => $region]);
            $session = $sts->assumeRole([
                'RoleArn' => $roleArn,
                'RoleSessionName' => 'caregiver-'.$document->id.'-user-'.$actorId,
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
            $command = $s3->getCommand('GetObject', [
                'Bucket' => $bucket, 'Key' => $key,
                'ResponseContentDisposition' => 'attachment; filename="'.str_replace(["\"", "\r", "\n"], '', basename($document->original_name)).'"',
                'ResponseContentType' => $document->mime_type,
            ]);
            $expiresAt = now()->addSeconds(300);
            $url = (string) $s3->createPresignedRequest($command, $expiresAt)->getUri();
            $this->recordReveal($document, $actorId, $reasonCode, 'Allowed', $expiresAt);
            activity('caregiver_document_reveal')->causedBy($actorId)->performedOn($document)
                ->withProperties(['reason_code' => $reasonCode, 'result' => 'allowed', 'expires_at' => $expiresAt->toIso8601String()])
                ->log('Caregiver document reveal URL issued');

            return $url;
        } catch (\Throwable $exception) {
            $this->recordReveal($document, $actorId, $reasonCode, 'Failed');
            activity('caregiver_document_reveal')->causedBy($actorId)->performedOn($document)
                ->withProperties(['reason_code' => $reasonCode, 'result' => 'failed'])
                ->log('Caregiver document reveal failed');
            throw $exception;
        }
    }

    private function recordReveal(CaregiverDocument $document, int $actorId, string $reasonCode, string $result, $expiresAt = null): void
    {
        CaregiverDocumentReveal::query()->create([
            'caregiver_document_id' => $document->id,
            'actor_id' => $actorId,
            'reason_code' => $reasonCode,
            'attempted_at' => now(),
            'expires_at' => $expiresAt,
            'result' => $result,
            'ip_address' => request()->ip(),
        ]);
    }
}
