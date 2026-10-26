@extends('layouts.base')
@section('title', 'Edit Service')

@php
    $breadcrumb = [
        ['title' => 'Catalog'],
        ['title' => 'Services', 'url' => route('services.index')],
        ['title' => 'Edit — ' . $service->name],
    ];
@endphp

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <x-alert :show_validations="false" />
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('services.update', $service->id) }}" method="POST">
                    @csrf @method('PUT')

                    <x-form-group>
                        <x-form-label for="code" required="true">Code</x-form-label>
                        <input type="text" name="code" id="code"
                            class="form-control @error('code') is-invalid @enderror"
                            value="{{ old('code', $service->code) }}" maxlength="50" required>
                        <x-input-error :messages="$errors->get('code')" />
                    </x-form-group>

                    <x-form-group>
                        <x-form-label for="name" required="true">Name</x-form-label>
                        <input type="text" name="name" id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $service->name) }}" maxlength="150" required>
                        <x-input-error :messages="$errors->get('name')" />
                    </x-form-group>

                    <x-form-group>
                        <x-form-label for="variant" required="true">Variant</x-form-label>
                        <select name="variant" id="variant" class="form-select" required>
                            @foreach ($variants as $v)
                                <option value="{{ $v }}" {{ old('variant', $service->variant) === $v ? 'selected' : '' }}>
                                    {{ $v === 'n/a' ? 'Not Applicable' : ucfirst($v) }}
                                </option>
                            @endforeach
                        </select>
                    </x-form-group>

                    <x-form-group>
                        <x-form-label for="description">Description</x-form-label>
                        <textarea name="description" id="description" class="form-control"
                            rows="3" maxlength="500">{{ old('description', $service->description) }}</textarea>
                    </x-form-group>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Update Service</button>
                        <a href="{{ route('services.show', $service->id) }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
