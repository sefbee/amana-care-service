<?php

namespace App\Admin\Services;

use App\Admin\Models\Lead;
use App\Admin\Models\OperationalNotification;
use App\Admin\Models\Role;
use App\Admin\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class OperationalNotificationDispatcher
{
    private const RETRY_MINUTES = [1, 5, 30, 120];

    /** @return array{delivered:int,pending:int,cancelled:int,dead_lettered:int,quiet_deferred:int,quiet_policy_unconfigured:int} */
    public function run(): array
    {
        OperationalNotification::query()->where('status', 'Sending')
            ->where('reserved_at', '<=', now()->subMinutes(10))
            ->update(['status' => 'Pending', 'reserved_at' => null, 'next_attempt_at' => now()]);

        $result = ['delivered' => 0, 'pending' => 0, 'cancelled' => 0, 'dead_lettered' => 0, 'quiet_deferred' => 0, 'quiet_policy_unconfigured' => 0];
        $ids = OperationalNotification::query()->where('status', 'Pending')
            ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->orderByRaw("CASE WHEN priority = 'high' THEN 0 ELSE 1 END")
            ->orderBy('id')->limit(200)->pluck('id');

        foreach ($ids as $id) {
            $snapshot = OperationalNotification::query()->find($id);
            if (! $snapshot) {
                continue;
            }

            if (in_array($snapshot->channel, ['email', 'sms'], true)) {
                $quiet = $this->quietWindow();
                if ($quiet['state'] !== 'open') {
                    $deferredUntil = $quiet['until'] ?? now()->addMinutes(15);
                    OperationalNotification::query()->whereKey($id)->where('status', 'Pending')->update(['next_attempt_at' => $deferredUntil]);
                    $result[$quiet['state'] === 'unconfigured' ? 'quiet_policy_unconfigured' : 'quiet_deferred']++;
                    activity('notification_delivery')->performedOn($snapshot)
                        ->withProperties([
                            'notification_id' => $snapshot->id,
                            'channel' => $snapshot->channel,
                            'priority' => $snapshot->priority,
                            'result' => $quiet['state'] === 'unconfigured' ? 'quiet_policy_unconfigured' : 'quiet_hours_deferred',
                            'retry_at' => $deferredUntil->toIso8601String(),
                        ])->log('Notification delivery deferred');
                    continue;
                }
            }

            $notification = DB::transaction(function () use ($id): ?OperationalNotification {
                $locked = OperationalNotification::query()->lockForUpdate()->find($id);
                if (! $locked || $locked->status !== 'Pending' || ($locked->next_attempt_at && $locked->next_attempt_at->isFuture())) {
                    return null;
                }
                $locked->forceFill([
                    'status' => 'Sending',
                    'attempt_count' => $locked->attempt_count + 1,
                    'reserved_at' => now(),
                ])->save();

                return $locked;
            });

            if (! $notification) {
                continue;
            }

            [$success, $errorCode, $cancel] = $this->deliver($notification);
            $this->finish($notification, $success, $errorCode, $cancel, $result);
        }

        return $result;
    }

    /** @return array{state:string,until:?CarbonImmutable} */
    private function quietWindow(): array
    {
        $start = config('notifications.quiet_hours.start');
        $end = config('notifications.quiet_hours.end');
        $timezone = config('notifications.quiet_hours.timezone');
        if (! is_string($start) || ! is_string($end) || ! is_string($timezone)
            || preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start) !== 1
            || preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end) !== 1
            || $start === $end) {
            return ['state' => 'unconfigured', 'until' => null];
        }

        try {
            $localNow = CarbonImmutable::now($timezone);
        } catch (\Throwable) {
            return ['state' => 'unconfigured', 'until' => null];
        }

        $time = $localNow->format('H:i');
        $quiet = $start < $end
            ? ($time >= $start && $time < $end)
            : ($time >= $start || $time < $end);
        if (! $quiet) {
            return ['state' => 'open', 'until' => null];
        }

        [$hour, $minute] = array_map('intval', explode(':', $end));
        $until = $localNow->startOfDay()->setTime($hour, $minute);
        if ($until->lessThanOrEqualTo($localNow)) {
            $until = $until->addDay();
        }

        return ['state' => 'deferred', 'until' => $until->utc()];
    }

    /** @return array{0:bool,1:?string,2:bool} */
    private function deliver(OperationalNotification $notification): array
    {
        if ($notification->lead_id) {
            $lead = Lead::query()->find($notification->lead_id);
            if (! $lead) {
                return [false, 'lead_missing', true];
            }
            if ($notification->created_by_user_id) {
                $actor = User::query()->whereKey($notification->created_by_user_id)->where('is_active', true)->first();
                if (! $actor) {
                    return [false, 'actor_unavailable', true];
                }
                if (! $actor->isPlatformSuperuser()) {
                    $role = Role::query()->whereKey($notification->created_by_role_id)
                        ->where('is_active', true)->whereNull('deleted_at')
                        ->whereHas('users', fn ($query) => $query->where('users.id', $actor->id)->wherePivot('is_active', true))
                        ->first();
                    if (! $role || ! in_array($role->slug, ['om', 'co'], true) || ($role->slug === 'co' && (int) $lead->owner_id !== (int) $actor->id)) {
                        return [false, 'actor_record_access_revoked', true];
                    }
                }
                if (! $actor->isPlatformSuperuser() && $lead->household_id) {
                    $blocked = DB::table('record_conflict_blocks')
                        ->where('staff_user_id', $notification->created_by_user_id)
                        ->where('household_id', $lead->household_id)
                        ->whereNull('revoked_at')
                        ->exists();
                    if ($blocked) {
                        return [false, 'record_access_revoked', true];
                    }
                }
            }
            $consentChannel = $notification->channel === 'email' ? 'Email' : 'SMS';
            $leadService = app(LeadService::class);
            if ($leadService->currentConsentStatus($lead) !== 'Granted'
                || ! $leadService->hasCurrentConsentForChannel($lead, $consentChannel)) {
                return [false, 'consent_not_current', true];
            }
            $address = $notification->channel === 'email' ? trim((string) $lead->email) : trim((string) $lead->phone);
        } else {
            $user = User::query()->whereKey($notification->recipient_user_id)->where('is_active', true)->first();
            if (! $user) {
                return [false, 'staff_recipient_unavailable', true];
            }
            $address = $notification->channel === 'email' ? trim((string) $user->email) : trim((string) $user->phone);
        }

        if ($notification->channel === 'email') {
            if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
                return [false, 'recipient_email_unavailable', true];
            }
            try {
                Mail::raw($notification->body, function ($message) use ($address, $notification): void {
                    $message->to($address)->subject($notification->title);
                });

                return [true, null, false];
            } catch (\Throwable) {
                return [false, 'email_transport_error', false];
            }
        }

        if ($notification->channel === 'sms') {
            if ($address === '') {
                return [false, 'recipient_phone_unavailable', true];
            }
            $url = (string) config('services.notifications_sms.url');
            $parts = parse_url($url);
            $apiKey = (string) config('services.notifications_sms.api_key');
            $secretKey = (string) config('services.notifications_sms.secret_key');
            $senderId = (string) config('services.notifications_sms.sender_id');
            if (! $parts || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || $apiKey === '' || $secretKey === '' || $senderId === '') {
                return [false, 'sms_provider_unconfigured', false];
            }
            try {
                $response = Http::timeout(8)->acceptJson()->get($url, [
                    'apikey' => $apiKey,
                    'secretkey' => $secretKey,
                    'callerID' => $senderId,
                    'toUser' => $address,
                    'messageContent' => $notification->body,
                ]);

                return $response->successful()
                    ? [true, null, false]
                    : [false, 'sms_provider_rejected', false];
            } catch (\Throwable) {
                return [false, 'sms_transport_error', false];
            }
        }

        return [false, 'unsupported_channel', true];
    }

    private function finish(OperationalNotification $notification, bool $success, ?string $errorCode, bool $cancel, array &$result): void
    {
        DB::transaction(function () use ($notification, $success, $errorCode, $cancel, &$result): void {
            $locked = OperationalNotification::query()->lockForUpdate()->find($notification->id);
            if (! $locked || $locked->status !== 'Sending') {
                return;
            }

            if ($success) {
                $locked->forceFill(['status' => 'Delivered', 'delivered_at' => now(), 'reserved_at' => null, 'next_attempt_at' => null, 'last_error_code' => null])->save();
                $result['delivered']++;
                $state = 'delivered';
            } elseif ($cancel) {
                $locked->forceFill(['status' => 'Cancelled', 'reserved_at' => null, 'next_attempt_at' => null, 'last_error_code' => $errorCode])->save();
                $result['cancelled']++;
                $state = 'cancelled';
            } else {
                $dead = $locked->attempt_count > count(self::RETRY_MINUTES);
                $locked->forceFill([
                    'status' => $dead ? 'DeadLetter' : 'Pending',
                    'reserved_at' => null,
                    'next_attempt_at' => $dead ? null : now()->addMinutes(self::RETRY_MINUTES[$locked->attempt_count - 1]),
                    'last_error_code' => $errorCode,
                ])->save();
                if ($dead) {
                    $result['dead_lettered']++;
                    $state = 'dead_letter';
                } else {
                    $result['pending']++;
                    $state = 'retry_scheduled';
                }
            }

            activity('notification_delivery')->performedOn($locked)
                ->withProperties([
                    'notification_id' => $locked->id,
                    'channel' => $locked->channel,
                    'priority' => $locked->priority,
                    'attempt_count' => $locked->attempt_count,
                    'error_code' => $errorCode,
                    'next_attempt_at' => $locked->next_attempt_at?->toIso8601String(),
                    'result' => $state,
                ])->log('Operational notification delivery attempt recorded');
        });
    }
}
