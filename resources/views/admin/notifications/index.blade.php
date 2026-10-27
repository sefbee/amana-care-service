@extends('layouts.base')
@section('title', 'Notifications')
@php($breadcrumb = [['title' => 'Notifications']])

@section('content')
    <div class="row">
        <div class="col-md-12">
            <x-alert :show_validations="false" />
            <div class="card shadow-sm">
                <div class="card-header"><h6 class="card-title mb-0 fw-bold">Your in-app notifications</h6></div>
                <div class="card-body">
                    @forelse ($notifications as $notification)
                        <div class="border rounded p-4 mb-3 {{ $notification->read_at ? 'bg-light' : 'border-primary' }}">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <strong>{{ $notification->title }}</strong>
                                        <span class="badge badge-light-{{ $notification->priority === 'high' ? 'danger' : 'secondary' }}">{{ ucfirst($notification->priority) }}</span>
                                        @unless ($notification->read_at)<span class="badge badge-light-primary">New</span>@endunless
                                    </div>
                                    <p class="mb-1">{{ $notification->body }}</p>
                                    <small class="text-muted">{{ $notification->delivered_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') }}</small>
                                </div>
                                @unless ($notification->read_at)
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-light-primary" type="submit">Mark read</button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-muted mb-0 py-5">You have no in-app notifications.</p>
                    @endforelse
                    {{ $notifications->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
