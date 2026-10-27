@extends('layouts.base')
@section('title', 'Website Settings')

@php
    $breadcrumb = [
        ['title' => 'Website', 'url' => route('cms.pages.index')],
        ['title' => 'Settings'],
    ];
@endphp

@section('actions')
    <form action="{{ route('cms.settings.publish') }}" method="POST" data-confirm="Publish all saved website settings?">
        @csrf
        <button type="submit" class="btn btn-success btn-sm" @disabled(!$hasPendingChanges)>Publish Settings</button>
    </form>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <x-alert :show_validations="false" />
        <div class="alert {{ $hasPendingChanges ? 'alert-warning' : 'alert-success' }}">
            {{ $hasPendingChanges ? 'Draft settings differ from the public website. An Operations Manager must publish them.' : 'Website settings match the currently published values.' }}
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('cms.settings.update') }}" method="POST">
                    @csrf @method('PUT')
                    @foreach($fields as $key => $field)
                        @php $value = old('settings.'.$key, $settings[$key] ?? null); @endphp
                        <div class="mb-4">
                            <label for="setting-{{ $key }}" class="form-label">{{ $field['label'] }}</label>
                            @if($field['type'] === 'media')
                                <select name="settings[{{ $key }}]" id="setting-{{ $key }}" class="form-select">
                                    <option value="">No logo selected</option>
                                    @foreach($media as $image)
                                        <option value="{{ $image->id }}" @selected((string) $value === (string) $image->id)>{{ $image->original_filename }}{{ $image->alt_text ? ' — '.$image->alt_text : '' }}</option>
                                    @endforeach
                                </select>
                            @elseif($field['type'] === 'textarea')
                                <textarea name="settings[{{ $key }}]" id="setting-{{ $key }}" rows="3" maxlength="{{ $field['max'] }}" class="form-control">{{ $value }}</textarea>
                            @else
                                <input type="{{ $field['type'] }}" name="settings[{{ $key }}]" id="setting-{{ $key }}" @if(isset($field['max'])) maxlength="{{ $field['max'] }}" @endif value="{{ $value }}" class="form-control">
                            @endif
                            <x-input-error :messages="$errors->get('settings.'.$key)" />
                        </div>
                    @endforeach
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                        <a href="{{ route('dashboard') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
