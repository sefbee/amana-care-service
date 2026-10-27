@extends('layouts.base')
@section('title', 'Payments')
@php($breadcrumb = [['title' => 'Finance & Operations'], ['title' => 'Payments']])

@section('actions')
    @if ($canReconcile)<a href="{{ route('finance.payments.create') }}" class="btn btn-sm btn-primary">Record payment</a>@endif
@endsection

@section('content')
    <div class="row"><div class="col-md-12">
        @unless ($canReconcile)<div class="alert alert-info">Read-only payment register. Finance Officer records and reconciles payments.</div>@endunless
        <div class="card"><div class="card-body">
            @php
                $columns = [
                    ['data' => 'receipt_number', 'name' => 'receipt_number', 'th' => 'Receipt'],
                    ['data' => 'household_name', 'name' => 'household.display_name', 'th' => 'Household'],
                    ['data' => 'paid_at', 'name' => 'paid_at', 'th' => 'Payment date'],
                    ['data' => 'method', 'name' => 'method'],
                    ['data' => 'amount', 'name' => 'amount'],
                    ['data' => 'status', 'name' => 'status'],
                    ['data' => 'action', 'name' => 'action'],
                ];
                $url = route('finance.payments.index');
            @endphp
            <x-datatable :columns="$columns" :url="$url" id="finance_payments_table" />
        </div></div>
    </div></div>
@endsection
