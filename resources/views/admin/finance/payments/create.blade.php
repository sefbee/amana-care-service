@extends('layouts.base')
@section('title', 'Record payment')
@php($breadcrumb = [['title' => 'Finance & Operations'], ['title' => 'Payments', 'url' => route('finance.payments.index')], ['title' => 'Record payment']])

@section('content')
    <div class="row"><div class="col-md-8 offset-md-2">
        <x-alert :show_validations="false" />
        <form method="POST" action="{{ route('finance.payments.store') }}" class="card shadow-sm">
            @csrf
            <div class="card-body">
                <p class="text-muted">Record funds received by Amanah. Payments remain unreconciled until matched to a bank statement.</p>
                <x-form-group><x-form-label for="method" required="true">Payment method</x-form-label><select class="form-select" name="method" id="method" required><option value="">Select method</option>@foreach ($methods as $method)<option value="{{ $method }}" @selected(old('method') === $method)>{{ $method }}</option>@endforeach</select></x-form-group>
                <x-form-group><x-form-label for="reference_id" required="true">Payment reference</x-form-label><input class="form-control" name="reference_id" id="reference_id" maxlength="150" required value="{{ old('reference_id') }}"></x-form-group>
                <x-form-group><x-form-label for="paid_at" required="true">Date received</x-form-label><input class="form-control" type="date" name="paid_at" id="paid_at" max="{{ today()->toDateString() }}" required value="{{ old('paid_at', today()->toDateString()) }}"></x-form-group>
                <hr><h6 class="fw-bold">Invoice allocations</h6>
                <div id="allocations">
                    <div class="row g-2 mb-3 allocation-row">
                        <div class="col-md-8"><select class="form-select" name="allocations[0][invoice_id]" required><option value="">Select invoice</option>@foreach ($invoices as $invoice)<option value="{{ $invoice->id }}" data-household="{{ $invoice->household_id }}" @selected(old('allocations.0.invoice_id') == $invoice->id)>{{ $invoice->invoice_number }} · {{ $invoice->household->display_name }} · BDT {{ $invoice->balance }} due</option>@endforeach</select></div>
                        <div class="col-md-4"><input class="form-control" name="allocations[0][amount]" inputmode="decimal" pattern="\d{1,10}(\.\d{1,2})?" placeholder="Amount (BDT)" required value="{{ old('allocations.0.amount') }}"></div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-light-primary" id="add-allocation">Add invoice allocation</button>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2"><a href="{{ route('finance.payments.index') }}" class="btn btn-light">Cancel</a><button class="btn btn-primary">Record payment</button></div>
        </form>
    </div></div>
@endsection

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.getElementById('add-allocation').addEventListener('click', function () {
        const container = document.getElementById('allocations');
        const index = container.querySelectorAll('.allocation-row').length;
        const first = container.querySelector('.allocation-row');
        const row = first.cloneNode(true);
        row.querySelectorAll('select, input').forEach(function (field) {
            field.name = field.name.replace(/allocations\[\d+\]/, 'allocations[' + index + ']');
            field.value = '';
        });
        container.appendChild(row);
    });
</script>
@endpush
