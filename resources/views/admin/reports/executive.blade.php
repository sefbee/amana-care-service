@extends('layouts.base')
@section('title', 'Executive Reports')
@php($breadcrumb = [['title' => 'Executive Reports']])

@section('actions')
    <a href="{{ route('executive.reports.export', ['start_month' => $startMonth, 'end_month' => $endMonth]) }}" class="btn btn-sm btn-primary">
        <i class="fa-solid fa-file-excel me-1"></i> Export XLSX
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <x-alert :show_validations="false" />
            <div class="card shadow-sm mb-5">
                <div class="card-header"><h6 class="card-title mb-0 fw-bold">Reporting period</h6></div>
                <div class="card-body">
                    <form method="GET" action="{{ route('executive.reports.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="start_month" class="form-label">From month</label>
                            <input id="start_month" name="start_month" type="month" class="form-control" value="{{ $startMonth }}" required>
                        </div>
                        <div class="col-md-3">
                            <label for="end_month" class="form-label">To month</label>
                            <input id="end_month" name="end_month" type="month" class="form-control" value="{{ $endMonth }}" required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-light-primary">Apply period</button>
                        </div>
                        <div class="col-md-12 text-muted">This report contains monthly totals only. It does not include household, caregiver, lead, invoice, or payment-level records.</div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header"><h6 class="card-title mb-0 fw-bold">Monthly operating and finance aggregates</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-row-bordered table-striped align-middle gs-5 gy-4">
                            <thead>
                                <tr class="fw-semibold text-gray-800">
                                    <th>Month</th>
                                    <th class="text-end">Leads created</th>
                                    <th class="text-end">Households created</th>
                                    <th class="text-end">Placements activated</th>
                                    <th class="text-end">Invoices issued</th>
                                    <th class="text-end">Invoice total (BDT)</th>
                                    <th class="text-end">Reconciled payments</th>
                                    <th class="text-end">Reconciled amount (BDT)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($months as $month)
                                    <tr>
                                        <td>{{ $month['month'] }}</td>
                                        <td class="text-end">{{ number_format($month['leads']) }}</td>
                                        <td class="text-end">{{ number_format($month['households']) }}</td>
                                        <td class="text-end">{{ number_format($month['placements']) }}</td>
                                        <td class="text-end">{{ number_format($month['invoices']) }}</td>
                                        <td class="text-end">{{ number_format((float) $month['invoice_total'], 2) }}</td>
                                        <td class="text-end">{{ number_format($month['payments']) }}</td>
                                        <td class="text-end">{{ number_format((float) $month['payment_total'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted">No aggregate results for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
