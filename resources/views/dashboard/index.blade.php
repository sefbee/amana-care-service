@extends('layouts.base')

@section('title', 'Dashboard')

@section('breadcrumb')

@endsection

@section('content')

    <div class="container-fluid py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-8 p-lg-12">
                <span class="badge badge-light-primary mb-4">Workspace</span>
                <h2 class="text-gray-900 fw-bold mb-3">Welcome to {{ config('app.name') }}</h2>
                <p class="text-gray-600 fs-5 mb-0">
                    {{ config('starter.purpose') }}
                </p>
            </div>
        </div>
    </div>

@endsection
