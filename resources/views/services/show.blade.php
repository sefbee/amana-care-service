@extends('layouts.base')
@section('title', $service->name)

@php
    $breadcrumb = [
        ['title' => 'Catalog'],
        ['title' => 'Services', 'url' => route('services.index')],
        ['title' => $service->name],
    ];
@endphp

@section('actions')
    <a href="{{ route('services.edit', $service->id) }}" class="btn btn-primary btn-sm">Edit</a>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="card-title mb-0 fw-bold">Service Details</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Code</strong><div>{{ $service->code }}</div></div>
                    <div class="col-md-4"><strong>Variant</strong><div>{{ $service->variant === 'n/a' ? 'Not Applicable' : ucfirst($service->variant) }}</div></div>
                    <div class="col-md-4"><strong>Status</strong><div>{{ $service->is_active ? 'Active' : 'Inactive' }}</div></div>
                    @if ($service->description)
                        <div class="col-12"><strong>Description</strong><div class="text-muted">{{ $service->description }}</div></div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Add Price Version --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="card-title mb-0 fw-bold">Add Price Version</h6></div>
            <div class="card-body">
                <form action="{{ route('services.price-versions.store', $service->id) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <x-form-group>
                                <x-form-label for="amount" required="true">Amount (BDT)</x-form-label>
                                <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                    step="0.01" min="0.01" required value="{{ old('amount') }}">
                                <x-input-error :messages="$errors->get('amount')" />
                            </x-form-group>
                        </div>
                        <div class="col-md-2">
                            <x-form-group>
                                <x-form-label for="unit" required="true">Unit</x-form-label>
                                <input type="text" name="unit" class="form-control" value="{{ old('unit', 'per month') }}" maxlength="50" required>
                            </x-form-group>
                        </div>
                        <div class="col-md-3">
                            <x-form-group>
                                <x-form-label for="price_type" required="true">Price Type</x-form-label>
                                <select name="price_type" class="form-select" required>
                                    <option value="Indicative Starting Price" {{ old('price_type') === 'Indicative Starting Price' ? 'selected' : '' }}>Indicative Starting Price</option>
                                    <option value="Fixed" {{ old('price_type') === 'Fixed' ? 'selected' : '' }}>Fixed</option>
                                </select>
                            </x-form-group>
                        </div>
                        <div class="col-md-2">
                            <x-form-group>
                                <x-form-label for="effective_from" required="true">Effective From</x-form-label>
                                <input type="date" name="effective_from" class="form-control" required value="{{ old('effective_from') }}">
                            </x-form-group>
                        </div>
                        <div class="col-md-2">
                            <x-form-group>
                                <x-form-label for="effective_to">Effective To</x-form-label>
                                <input type="date" name="effective_to" class="form-control" value="{{ old('effective_to') }}">
                            </x-form-group>
                        </div>
                    </div>
                    <x-form-group>
                        <x-form-label for="source_note">Source Note</x-form-label>
                        <input type="text" name="source_note" class="form-control" maxlength="255"
                            placeholder="e.g. W3 value, Unverified" value="{{ old('source_note') }}">
                    </x-form-group>
                    <button type="submit" class="btn btn-primary btn-sm">Add Price Version</button>
                </form>
            </div>
        </div>

        {{-- Price Versions List --}}
        <div class="card shadow-sm">
            <div class="card-header"><h6 class="card-title mb-0 fw-bold">Price Versions</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-hover table-row-bordered table-striped gs-5 gy-4 border rounded mb-0" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Unit</th>
                            <th>Effective From</th>
                            <th>Effective To</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Approved By</th>
                            <th>Source</th>
                            <th class="dt-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($service->priceVersions->sortByDesc('effective_from') as $price)
                        <tr>
                            <td class="fw-bold">{{ $price->formatted_amount }}</td>
                            <td>
                                <span class="badge badge-light-{{ $price->price_type === 'Fixed' ? 'success' : 'warning' }}">
                                    {{ $price->price_type === 'Fixed' ? 'Fixed' : 'Indicative' }}
                                </span>
                            </td>
                            <td>{{ $price->unit }}</td>
                            <td>{{ $price->effective_from->format('d M Y') }}</td>
                            <td>{{ $price->effective_to ? $price->effective_to->format('d M Y') : '—' }}</td>
                            <td>{!! $price->status_badge !!}</td>
                            <td>{{ $price->creator->name ?? '—' }}</td>
                            <td>{{ $price->approver->name ?? '—' }}</td>
                            <td><small class="text-muted">{{ $price->source_note ?? '—' }}</small></td>
                            <td class="text-end">
                                @if ($price->status === 'Draft')
                                    <form action="{{ route('services.price-versions.approve', [$service->id, $price->id]) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-light-success btn-sm btn-icon" title="Approve"
                                            onclick="return confirm('Approve this price version?')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('services.price-versions.destroy', [$service->id, $price->id]) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-light-danger btn-sm btn-icon" title="Delete"
                                            onclick="return confirm('Delete this price version?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                @elseif ($price->status === 'Approved')
                                    <form action="{{ route('services.price-versions.retire', [$service->id, $price->id]) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-light-secondary btn-sm" title="Retire"
                                            onclick="return confirm('Retire this price version?')">
                                            Retire
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">No price versions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
