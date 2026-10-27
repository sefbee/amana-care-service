<?php

namespace App\Admin\Services;

use App\Admin\Authorization\CrmRecordAccess;
use App\Admin\Models\Lead;
use App\Admin\Models\OperationalNotification;
use App\Admin\Models\User;
use Illuminate\Support\Facades\DB;

class OperationalNotificationService
{
    /** @param list<string> $channels
     *  @return list<OperationalNotification>
     */
    public function notifyStaff(User $recipient, string $type, string $title, string $body, array $channels, string $idempotencyKey, string $priority = 'normal', ?User $actor = null): array
    {
        return $this->enqueue($recipient, null, $type, $title, $body, $channels, $idempotencyKey, $priority, $actor);
    }

    /** @param list<string> $channels
     *  @return list<OperationalNotification>
     */
    public function notifyLead(Lead $recipient, string $type, string $title, string $body, array $channels, string $idempotencyKey, string $priority = 'normal', ?User $actor = null): array
    {
        abort_unless(collect($channels)->every(fn (string $channel): bool => in_array($channel, ['email', 'sms'], true)), 422, 'Lead notifications may only use Email or SMS.');
        if ($actor) {
            app(CrmRecordAccess::class)->assertLead($actor, $recipient);
        }

        return $this->enqueue(null, $recipient, $type, $title, $body, $channels, $idempotencyKey, $priority, $actor);
    }

    /** @param list<string> $channels
     *  @return list<OperationalNotification>
     */
    private function enqueue(?User $user, ?Lead $lead, string $type, string $title, string $body, array $channels, string $idempotencyKey, string $priority, ?User $actor): array
    {
        abort_if($channels === [], 422, 'Select at least one notification channel.');
        abort_unless(preg_match('/^[A-Za-z0-9._:-]{1,80}$/', $type) === 1, 422, 'Invalid notification type.');
        abort_unless(trim($title) !== '' && mb_strlen($title) <= 200, 422, 'Notification title is required and must be 200 characters or less.');
        abort_unless(trim($body) !== '' && mb_strlen($body) <= 10000, 422, 'Notification body is required and must be 10,000 characters or less.');
        abort_unless(in_array($priority, ['normal', 'high'], true), 422, 'Invalid notification priority.');
        abort_unless(preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $idempotencyKey) === 1, 422, 'Invalid notification idempotency key.');
        abort_unless(collect($channels)->unique()->count() === count($channels), 422, 'Notification channels must be unique.');
        abort_unless(collect($channels)->every(fn (string $channel): bool => in_array($channel, OperationalNotification::CHANNELS, true)), 422, 'Unsupported notification channel.');

        return DB::transaction(function () use ($user, $lead, $type, $title, $body, $channels, $idempotencyKey, $priority, $actor): array {
            $notifications = [];
            $actorRoleId = $actor && ! $actor->isPlatformSuperuser()
                ? (int) session('role_id', $actor->primary_role_id)
                : null;
            foreach ($channels as $channel) {
                $key = $idempotencyKey.':'.$channel;
                $notification = OperationalNotification::query()->firstOrCreate(
                    ['idempotency_key' => $key],
                    [
                        'notification_type' => $type,
                        'recipient_user_id' => $user?->id,
                        'lead_id' => $lead?->id,
                        'created_by_user_id' => $actor?->id,
                        'created_by_role_id' => $actorRoleId ?: null,
                        'channel' => $channel,
                        'priority' => $priority,
                        'title' => $title,
                        'body' => $body,
                        'status' => $channel === 'in_app' ? 'Delivered' : 'Pending',
                        'delivered_at' => $channel === 'in_app' ? now() : null,
                        'next_attempt_at' => $channel === 'in_app' ? null : now(),
                    ]
                );
                if (! $notification->wasRecentlyCreated && (
                    $notification->notification_type !== $type
                    || (int) $notification->recipient_user_id !== (int) $user?->id
                    || (int) $notification->lead_id !== (int) $lead?->id
                    || (int) $notification->created_by_user_id !== (int) $actor?->id
                    || (int) $notification->created_by_role_id !== (int) ($actorRoleId ?: null)
                    || $notification->priority !== $priority
                    || $notification->title !== $title
                    || $notification->body !== $body
                )) {
                    throw new \LogicException('An idempotency key cannot be reused for a different notification.');
                }
                $notifications[] = $notification;
                if ($notification->wasRecentlyCreated) {
                    activity('notification_outbox')->causedBy($actor)->performedOn($notification)
                        ->withProperties([
                            'notification_id' => $notification->id,
                            'notification_type' => $type,
                            'channel' => $channel,
                            'priority' => $priority,
                            'recipient_user_id' => $user?->id,
                            'lead_id' => $lead?->id,
                        ])->log('Operational notification enqueued');
                }
            }

            return $notifications;
        });
    }
}
