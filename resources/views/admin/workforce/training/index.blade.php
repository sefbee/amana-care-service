@extends('layouts.base')
@section('title', 'Training Records')
@php($breadcrumb = [['title' => 'Training Records']])

@section('content')
    <div class="row">
        <div class="col-md-12">
            <x-alert :show_validations="false" />
            @unless ($canRecord)
                <div class="alert alert-info">Read-only training roster. The Trainer records attendance and outcomes.</div>
            @endunless
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('training-records.index') }}" class="row g-3 align-items-end mb-4">
                        <div class="col-md-6">
                            <label for="training_module_id" class="form-label">Mandatory training module</label>
                            <select id="training_module_id" name="training_module_id" class="form-select" required>
                                <option value="">Select a module</option>
                                @foreach ($modules as $item)
                                    <option value="{{ $item->id }}" @selected($module?->id === $item->id)>{{ $item->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto"><button type="submit" class="btn btn-primary">Load roster</button></div>
                    </form>

                    @php($columns = [
                        ['data' => 'id', 'name' => 'id', 'visible' => false, 'searchable' => false],
                        ['data' => 'name', 'name' => 'name', 'th' => 'Caregiver'],
                        ['data' => 'outcome', 'name' => 'outcome', 'th' => 'Outcome', 'orderable' => false],
                        ['data' => 'attended_at', 'name' => 'attended_at', 'th' => 'Attendance date', 'orderable' => false],
                        ['data' => 'action', 'name' => 'action', 'th' => $canRecord ? 'Record attendance and outcome' : 'Actions', 'searchable' => false, 'orderable' => false],
                    ])
                    <x-datatable :columns="$columns" :url="route('training-records.index', ['training_module_id' => $module?->id])" id="training_records_table" />
                </div>
            </div>
        </div>
    </div>
@endsection
