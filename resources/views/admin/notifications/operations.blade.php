@extends('layouts.base')
@section('title', 'Notification Operations')
@php($breadcrumb = [['title' => 'Admin Console', 'url' => route('menus.index')], ['title' => 'Notification Operations']])

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="row g-3 mb-4">
                @foreach ([
                    'Pending' => 'Pending',
                    'Sending' => 'Sending',
                    'DeadLetter' => 'Dead letter',
                ] as $key => $label)
                    <div class="col-sm-6 col-xl-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted mb-1">{{ $label }}</div>
                                <div class="fs-3 fw-bold">{{ number_format((int) ($statusCounts[$key] ?? 0)) }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="col-sm-6 col-xl-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted mb-1">Due / stale reservations</div>
                            <div class="fs-3 fw-bold">{{ number_format($dueCount) }} / {{ number_format($staleSendingCount) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h6 class="card-title mb-0 fw-bold">Queue metadata</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (['Pending', 'Sending', 'DeadLetter'] as $filter)
                            <a class="btn btn-sm {{ $status === $filter ? 'btn-primary' : 'btn-light-primary' }}"
                               href="{{ route('notifications.operations', ['status' => $filter]) }}">{{ $filter === 'DeadLetter' ? 'Dead letter' : $filter }}</a>
                        @endforeach
                        <a class="btn btn-sm {{ $status === null ? 'btn-primary' : 'btn-light-primary' }}"
                           href="{{ route('notifications.operations') }}">All active</a>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">This view contains delivery metadata only. Message content, recipient details, and retry controls are intentionally excluded.</p>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="badge badge-light-primary">Email: {{ number_format((int) ($channelCounts['email'] ?? 0)) }}</span>
                        <span class="badge badge-light-primary">SMS: {{ number_format((int) ($channelCounts['sms'] ?? 0)) }}</span>
                        <span class="badge badge-light-secondary">In-app: {{ number_format((int) ($channelCounts['in_app'] ?? 0)) }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gy-4">
                            <thead>
                                <tr>
                                    <th>ID</th><th>Type</th><th>Channel</th><th>Priority</th><th>Status</th>
                                    <th>Attempts</th><th>Next attempt / reserved</th><th>Last error</th><th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($notifications as $notification)
                                    <tr>
                                        <td>{{ $notification->id }}</td>
                                        <td>{{ $notification->notification_type }}</td>
                                        <td>{{ strtoupper($notification->channel) }}</td>
                                        <td>{{ ucfirst($notification->priority) }}</td>
                                        <td>{{ $notification->status }}</td>
                                        <td>{{ $notification->attempt_count }}</td>
                                        <td>
                                            @if ($notification->status === 'Sending')
                                                {{ $notification->reserved_at?->timezone(config('app.timezone'))->format('d M Y, H:i:s') ?? '—' }}
                                            @else
                                                {{ $notification->next_attempt_at?->timezone(config('app.timezone'))->format('d M Y, H:i:s') ?? '—' }}
                                            @endif
                                        </td>
                                        <td>{{ $notification->last_error_code ?? '—' }}</td>
                                        <td>{{ $notification->created_at?->timezone(config('app.timezone'))->format('d M Y, H:i:s') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-5">No notifications match this filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $notifications->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
