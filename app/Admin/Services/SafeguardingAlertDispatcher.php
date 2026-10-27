<?php

namespace App\Admin\Services;

use App\Admin\Models\SafeguardingAlertDelivery;
use App\Admin\Models\SafeguardingIncidentAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SafeguardingAlertDispatcher
{
    private const RETRY_MINUTES = [1, 5, 30, 120];

    /** @return array{delivered:int, pending:int, dead_lettered:int, sla_breaches:int} */
    public function run(?array $deliveryIds = null): array
    {
        SafeguardingAlertDelivery::query()->where('status', 'Sending')
            ->where('reserved_at', '<=', now()->subMinutes(10))
            ->update(['status' => 'Pending', 'reserved_at' => null]);

        $query = SafeguardingAlertDelivery::query()->where('status', 'Pending')
            ->where(fn ($due) => $due->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->orderBy('id');
        if ($deliveryIds !== null) {
            $query->whereIn('id', $deliveryIds);
        }

        $delivered = 0;
        $pending = 0;
        $deadLettered = 0;
        foreach ($query->limit(200)->pluck('id') as $deliveryId) {
            $delivery = DB::transaction(function () use ($deliveryId): ?SafeguardingAlertDelivery {
                $delivery = SafeguardingAlertDelivery::query()->lockForUpdate()->find($deliveryId);
                if (! $delivery || $delivery->status !== 'Pending' || ($delivery->next_attempt_at && $delivery->next_attempt_at->isFuture())) {
                    return null;
                }
                $delivery->forceFill([
                    'status' => 'Sending',
                    'attempt_count' => $delivery->attempt_count + 1,
                    'reserved_at' => now(),
                ])->save();

                return $delivery->load(['alert.recipient', 'alert.incident']);
            });

            if (! $delivery) {
                continue;
            }

            [$success, $errorCode] = $this->deliver($delivery);
            DB::transaction(function () use ($delivery, $success, $errorCode, &$delivered, &$pending, &$deadLettered): void {
                $locked = SafeguardingAlertDelivery::query()->lockForUpdate()->findOrFail($delivery->id);
                if ($locked->status !== 'Sending') {
                    return;
                }
                if ($success) {
                    $locked->forceFill(['status' => 'Delivered', 'delivered_at' => now(), 'reserved_at' => null, 'next_attempt_at' => null, 'last_error_code' => null])->save();
                    $delivered++;
                    activity('safeguarding_alert_delivery')->performedOn($locked)
                        ->withProperties(['incident_alert_id' => $locked->incident_alert_id, 'channel' => $locked->channel, 'attempt_count' => $locked->attempt_count, 'result' => 'delivered'])
                        ->log('Safeguarding alert delivered');
                    return;
                }

                if ($errorCode === 'incident_already_acknowledged') {
                    $locked->forceFill(['status' => 'Cancelled', 'reserved_at' => null, 'next_attempt_at' => null, 'last_error_code' => $errorCode])->save();
                    activity('safeguarding_alert_delivery')->performedOn($locked)
                        ->withProperties(['incident_alert_id' => $locked->incident_alert_id, 'channel' => $locked->channel, 'result' => 'cancelled_after_acknowledgement'])
                        ->log('Safeguarding alert delivery cancelled after case acknowledgement');
                    return;
                }

                $terminal = $locked->attempt_count > count(self::RETRY_MINUTES);
                $locked->forceFill([
                    'status' => $terminal ? 'DeadLetter' : 'Pending',
                    'reserved_at' => null,
                    'next_attempt_at' => $terminal ? null : now()->addMinutes(self::RETRY_MINUTES[$locked->attempt_count - 1]),
                    'last_error_code' => $errorCode,
                ])->save();
                if ($terminal) {
                    $deadLettered++;
                    activity('safeguarding_alert_delivery')->performedOn($locked)
                        ->withProperties(['incident_alert_id' => $locked->incident_alert_id, 'channel' => $locked->channel, 'attempt_count' => $locked->attempt_count, 'error_code' => $errorCode, 'result' => 'dead_letter'])
                        ->log('Safeguarding alert moved to dead-letter state');
                } else {
                    $pending++;
                }
                activity('safeguarding_alert_delivery')->performedOn($locked)
                    ->withProperties([
                        'incident_alert_id' => $locked->incident_alert_id,
                        'channel' => $locked->channel,
                        'attempt_count' => $locked->attempt_count,
                        'error_code' => $errorCode,
                        'next_attempt_at' => $locked->next_attempt_at?->toIso8601String(),
                        'result' => $terminal ? 'dead_letter' : 'retry_scheduled',
                    ])->log('Safeguarding alert delivery attempt failed');
            });
        }

        return ['delivered' => $delivered, 'pending' => $pending, 'dead_lettered' => $deadLettered, 'sla_breaches' => $this->recordAcknowledgementBreaches()];
    }

    private function deliver(SafeguardingAlertDelivery $delivery): array
    {
        $user = $delivery->alert?->recipient;
        if (! $user || ! $delivery->alert?->incident) {
            return [false, 'recipient_or_case_missing'];
        }
        if ($delivery->alert->refresh()->acknowledged_at) {
            return [false, 'incident_already_acknowledged'];
        }

        $body = 'A high-priority restricted safeguarding case requires your attention. Sign in to Amanah Operations.';
        try {
            if ($delivery->channel === 'email') {
                if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    return [false, 'recipient_email_missing'];
                }
                Mail::raw($body, function ($message) use ($user): void {
                    $message->to($user->email)->subject('High-priority safeguarding alert');
                });

                return [true, null];
            }

            if ($delivery->channel === 'sms') {
                if (! $user->phone) {
                    return [false, 'recipient_phone_missing'];
                }
                if (! $this->sendSms($user->phone, $body)) {
                    return [false, 'sms_provider_rejected'];
                }

                return [true, null];
            }
        } catch (\Throwable) {
            return [false, 'provider_transport_error'];
        }

        return [false, 'unsupported_channel'];
    }

    private function sendSms(string $phone, string $message): bool
    {
        $url = (string) config('services.safeguarding_sms.url');
        $parts = parse_url($url);
        if (! $parts || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return false;
        }
        $apiKey = (string) config('services.safeguarding_sms.api_key');
        $secretKey = (string) config('services.safeguarding_sms.secret_key');
        $senderId = (string) config('services.safeguarding_sms.sender_id');
        if ($apiKey === '' || $secretKey === '' || $senderId === '') {
            return false;
        }

        $response = Http::timeout(8)->acceptJson()->get($url, [
            'apikey' => $apiKey,
            'secretkey' => $secretKey,
            'callerID' => $senderId,
            'toUser' => $phone,
            'messageContent' => $message,
        ]);

        return $response->successful();
    }

    private function recordAcknowledgementBreaches(): int
    {
        $breached = 0;
        SafeguardingIncidentAlert::query()->whereNull('acknowledged_at')->whereNull('sla_breached_at')
            ->where('created_at', '<=', now()->subMinutes(15))->orderBy('id')->chunkById(100, function ($alerts) use (&$breached): void {
                foreach ($alerts as $alert) {
                    DB::transaction(function () use ($alert, &$breached): void {
                        $locked = SafeguardingIncidentAlert::query()->lockForUpdate()->find($alert->id);
                        if (! $locked || $locked->acknowledged_at || $locked->sla_breached_at || $locked->created_at->gt(now()->subMinutes(15))) {
                            return;
                        }
                        $locked->forceFill(['sla_breached_at' => now()])->save();
                        activity('safeguarding_alert_sla')->performedOn($locked)
                            ->withProperties(['incident_id' => $locked->incident_id, 'priority' => $locked->priority, 'minutes_unacknowledged' => 15])
                            ->log('Safeguarding alert acknowledgement exceeded 15-minute target');
                        $breached++;
                    });
                }
            });

        return $breached;
    }
}
