@extends('layouts.base')
@section('title', 'Invoice '.$invoice->invoice_number)
@php($breadcrumb = [['title' => 'Finance & Operations'], ['title' => 'Invoices', 'url' => route('finance.invoices.index')], ['title' => $invoice->invoice_number]])

@section('actions')
    @if ($canIssue && $invoice->status === 'Draft')
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#issueInvoice">Issue invoice</button>
    @endif
@endsection

@section('content')
    <div class="row g-5">
        <div class="col-md-6"><div class="card h-100"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Billing</h6></div><div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-5">Household</dt><dd class="col-sm-7">{{ $invoice->household->display_name }}</dd>
                <dt class="col-sm-5">Billing contact</dt><dd class="col-sm-7">{{ $invoice->billing_contact_name }}<br>{{ $invoice->billing_contact_email }}<br>{{ $invoice->billing_contact_phone }}</dd>
                <dt class="col-sm-5">Period</dt><dd class="col-sm-7">{{ $invoice->period_start->format('d M Y') }} – {{ $invoice->period_end->format('d M Y') }}</dd>
                <dt class="col-sm-5">Status</dt><dd class="col-sm-7">{{ $invoice->status }}</dd>
                <dt class="col-sm-5">Due date</dt><dd class="col-sm-7">{{ $invoice->due_date?->format('d M Y') ?? 'Not issued' }}</dd>
                <dt class="col-sm-5">Total</dt><dd class="col-sm-7">BDT {{ \App\Admin\Services\FinanceMoney::decimal(\App\Admin\Services\FinanceMoney::cents($invoice->getRawOriginal('total'))) }}</dd>
                <dt class="col-sm-5">Paid</dt><dd class="col-sm-7">BDT {{ $invoice->paid_amount }}</dd>
                <dt class="col-sm-5">Balance</dt><dd class="col-sm-7">BDT {{ $invoice->balance }}</dd>
            </dl>
        </div></div></div>
        <div class="col-md-6"><div class="card h-100"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Invoice lines</h6></div><div class="table-responsive"><table class="table table-row-bordered align-middle mb-0"><thead><tr><th>Description</th><th>Quantity</th><th>Unit price</th><th class="text-end">Line total</th></tr></thead><tbody>
            @foreach ($invoice->lines as $line)
                <tr><td>{{ $line->description_snapshot }}</td><td>{{ $line->quantity }} {{ $line->unit_snapshot }}</td><td>BDT {{ $line->unit_amount }}</td><td class="text-end">BDT {{ $line->line_total }}</td></tr>
            @endforeach
            </tbody></table></div></div></div>
        @if ($canViewPayments)<div class="col-md-12"><div class="card"><div class="card-header"><h6 class="card-title mb-0 fw-bold">Payments</h6></div><div class="table-responsive"><table class="table table-row-bordered align-middle mb-0"><thead><tr><th>Receipt</th><th>Date</th><th>Method</th><th>Amount allocated</th><th>Status</th></tr></thead><tbody>
            @forelse ($invoice->payments as $payment)
                <tr><td><a href="{{ route('finance.payments.receipt', $payment) }}">{{ $payment->receipt_number }}</a></td><td>{{ $payment->paid_at->format('d M Y') }}</td><td>{{ $payment->method }}</td><td>BDT {{ $payment->pivot->amount }}</td><td>{{ $payment->status }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-muted">No payments recorded.</td></tr>
            @endforelse
        </tbody></table></div></div></div>@endif
    </div>

    @if ($canIssue && $invoice->status === 'Draft')
        <x-modal id="issueInvoice" title="Issue invoice">
            <form method="POST" action="{{ route('finance.invoices.issue', $invoice) }}">
                @csrf
                <div class="modal-body"><x-form-group><x-form-label for="due_date" required="true">Due date</x-form-label><input class="form-control" type="date" name="due_date" id="due_date" min="{{ today()->toDateString() }}" required></x-form-group><p class="text-muted mb-0">Select the due date according to the approved billing terms.</p></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Issue invoice</button></div>
            </form>
        </x-modal>
    @endif
@endsection
