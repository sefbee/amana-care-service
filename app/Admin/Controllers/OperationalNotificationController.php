<?php

namespace App\Admin\Controllers;

use App\Admin\Models\OperationalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationalNotificationController
{
    public function operations(Request $request)
    {
        abort_unless($request->user()?->isPlatformSuperuser(), 403);

        $now = now();
        $status = $request->query('status');
        $allowedStatuses = ['Pending', 'Sending', 'Delivered', 'Cancelled', 'DeadLetter'];
        abort_if($status !== null && ! in_array($status, $allowedStatuses, true), 404);

        $statusCounts = OperationalNotification::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $channelCounts = OperationalNotification::query()
            ->select('channel', DB::raw('COUNT(*) as total'))
            ->whereIn('status', ['Pending', 'Sending', 'DeadLetter'])
            ->groupBy('channel')
            ->pluck('total', 'channel');

        $dueCount = OperationalNotification::query()
            ->where('status', 'Pending')
            ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now))
            ->count();
        $staleSendingCount = OperationalNotification::query()
            ->where('status', 'Sending')
            ->where('reserved_at', '<=', $now->copy()->subMinutes(10))
            ->count();

        // Select only operational metadata. Never hydrate encrypted message bodies, titles, or recipients here.
        $notifications = OperationalNotification::query()
            ->select([
                'id', 'notification_type', 'channel', 'priority', 'status', 'attempt_count',
                'next_attempt_at', 'reserved_at', 'delivered_at', 'last_error_code', 'created_at',
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->whereIn('status', $status ? [$status] : ['Pending', 'Sending', 'DeadLetter'])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        activity('notification_operations_access')->causedBy($request->user())
            ->withProperties([
                'status_filter' => $status,
                'page' => $notifications->currentPage(),
                'pending' => (int) ($statusCounts['Pending'] ?? 0),
                'sending' => (int) ($statusCounts['Sending'] ?? 0),
                'dead_letter' => (int) ($statusCounts['DeadLetter'] ?? 0),
            ])
            ->log('Operational notification queue viewed');

        return view('admin.notifications.operations', compact(
            'statusCounts', 'channelCounts', 'dueCount', 'staleSendingCount', 'notifications', 'status'
        ));
    }

    public function index(Request $request)
    {
        $notifications = OperationalNotification::query()
            ->where('recipient_user_id', $request->user()->id)
            ->where('channel', 'in_app')
            ->where('status', 'Delivered')
            ->latest('id')
            ->paginate(20);

        activity('notification_inbox_access')->causedBy($request->user())
            ->withProperties(['page' => $notifications->currentPage()])
            ->log('Staff notification inbox viewed');

        return view('admin.notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, OperationalNotification $notification)
    {
        abort_unless(
            (int) $notification->recipient_user_id === (int) $request->user()->id
                && $notification->channel === 'in_app'
                && $notification->status === 'Delivered',
            404
        );

        DB::transaction(function () use ($request, $notification): void {
            $locked = OperationalNotification::query()->lockForUpdate()->findOrFail($notification->id);
            abort_unless((int) $locked->recipient_user_id === (int) $request->user()->id && $locked->channel === 'in_app', 404);
            if (! $locked->read_at) {
                $locked->forceFill(['read_at' => now()])->save();
            }
        });

        activity('notification_inbox_access')->causedBy($request->user())->performedOn($notification)
            ->withProperties(['notification_id' => $notification->id, 'action' => 'read'])
            ->log('Staff notification marked read');

        return back();
    }
}
