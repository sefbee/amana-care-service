@extends('layouts.base')
@section('title', 'Invoices')
@php($breadcrumb = [['title' => 'Finance & Operations'], ['title' => 'Invoices']])

@section('content')
    <div class="row"><div class="col-md-12">
        <div class="card"><div class="card-body">
            @php
                $columns = [
                    ['data' => 'invoice_number', 'name' => 'invoice_number', 'th' => 'Invoice'],
                    ['data' => 'household_name', 'name' => 'household.display_name', 'th' => 'Household'],
                    ['data' => 'period_start', 'name' => 'period_start', 'th' => 'Billing period'],
                    ['data' => 'total', 'name' => 'total', 'th' => 'Total'],
                    ['data' => 'balance', 'name' => 'balance', 'orderable' => false, 'searchable' => false],
                    ['data' => 'status', 'name' => 'status'],
                    ['data' => 'action', 'name' => 'action'],
                ];
                $url = route('finance.invoices.index');
            @endphp
            <x-datatable :columns="$columns" :url="$url" id="finance_invoices_table" />
        </div></div>
    </div></div>
@endsection
