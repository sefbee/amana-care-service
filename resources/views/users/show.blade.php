@extends('layouts.base')
@section('title', 'User Profile')

@php
    $breadcrumb = [
        ['title' => 'Users'],
        ['title' => 'Manage', 'url' => route('users.index')],
        ['title' => 'User Profile']
    ];
@endphp

@section('content')
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <x-alert :show_validations="false" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <!-- User Photo & Basic Info Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="position-relative me-3">
                            @if($user->photo_url)
                                <img src="{{ $user->photo_url }}" class="rounded-circle border border-2 border-primary" style="width: 60px; height: 60px; object-fit: cover;" alt="User Photo">
                            @else
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center border border-2 border-primary fw-bold" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <small class="text-muted">{{ $user->userid }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role & Access Card -->
            @if($user->user_type)
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="card-title mb-0 fw-bold">Role & Access</h6>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="text-center p-2 rounded {{ $user->isAdmin() ? 'bg-success bg-opacity-10' : 'bg-light' }}">
                                    <i class="fas fa-shield-alt fa-lg mb-1 text-{{ $user->isAdmin() ? 'success' : 'muted' }}"></i>
                                    <div class="small fw-medium text-{{ $user->isAdmin() ? 'success' : 'muted' }}">
                                        {{ $user->isAdmin() ? 'Admin' : 'User' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-2 rounded {{ $user->can_access_admin_panel ? 'bg-info bg-opacity-10' : 'bg-light' }}">
                                    <i class="fas fa-cog fa-lg mb-1 text-{{ $user->can_access_admin_panel ? 'info' : 'muted' }}"></i>
                                    <div class="small fw-medium text-{{ $user->can_access_admin_panel ? 'info' : 'muted' }}">
                                        {{ $user->can_access_admin_panel ? 'Panel' : 'No Panel' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-8">
            <!-- Personal Information Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-transparent border-0 pb-1">
                    <h6 class="card-title mb-0 fw-bold">Personal Information</h6>
                </div>
                <div class="card-body pt-2">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="mb-2">
                                <small class="text-muted">Full Name</small>
                                <div class="fw-medium">{{ $user->name }}</div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Email</small>
                                <div class="fw-medium d-flex align-items-center">
                                    {{ $user->email ?? 'Not provided' }}
                                    @if($user->email && $user->email_verified_at)
                                        <span class="badge bg-success bg-opacity-10 text-success ms-2 small">Verified</span>
                                    @elseif($user->email && !$user->email_verified_at)
                                        <span class="badge bg-warning bg-opacity-10 text-warning ms-2 small">Unverified</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Phone</small>
                                <div class="fw-medium">{{ $user->phone ?? 'Not provided' }}</div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Gender</small>
                                <div class="fw-medium">{{ $user->gender_text }}</div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-2">
                                <small class="text-muted">User Code</small>
                                <div class="fw-medium">{{ $user->user_code ?? 'Not assigned' }}</div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Designation</small>
                                <div class="fw-medium">{{ $user->designation ?? 'Not assigned' }}</div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Date of Birth</small>
                                <div class="fw-medium">
                                    @if($user->dob)
                                        {{ $user->dob->format('M d, Y') }}
                                        @if($user->age)
                                            <span class="text-muted small">({{ $user->age }} years)</span>
                                        @endif
                                    @else
                                        Not provided
                                    @endif
                                </div>
                            </div>
                            @if($user->address)
                                <div class="mb-2">
                                    <small class="text-muted">Address</small>
                                    <div class="fw-medium">{{ $user->address }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-1">
                    <h6 class="card-title mb-0 fw-bold">System Information</h6>
                </div>
                <div class="card-body pt-2">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="mb-2">
                                <small class="text-muted">Account Created</small>
                                <div class="fw-medium">{{ $user->created_at->format('M d, Y h:i A') }}</div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Last Updated</small>
                                <div class="fw-medium">{{ $user->updated_at->format('M d, Y h:i A') }}</div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            @if($user->email_verified_at)
                                <div class="mb-2">
                                    <small class="text-muted">Email Verified</small>
                                    <div class="fw-medium">{{ $user->email_verified_at->format('M d, Y h:i A') }}</div>
                                </div>
                            @endif
                            <div class="mb-2">
                                <small class="text-muted">Account Status</small>
                                <div>
                                    <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }} bg-opacity-10 text-{{ $user->is_active ? 'success' : 'danger' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    @if($user->deleted_at)
                                        <span class="badge bg-warning bg-opacity-10 text-warning ms-1">Soft Deleted</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $("#primary_role_id").select2();
            $("#kt_datepicker_2").flatpickr();
        });
    </script>
@endpush
