@extends('layouts.base')
@section('title', 'Household — ' . $household->display_name)

@php
    $breadcrumb = [
        ['title' => 'CRM'],
        ['title' => 'Households', 'url' => route('households.index')],
        ['title' => $household->display_name],
    ];
@endphp

@section('actions')
    <a href="{{ route('households.edit', $household->id) }}" class="btn btn-primary btn-sm">Edit</a>
@endsection

@section('tabs')
    <ul class="nav nav-tabs mt-3">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#details">Details</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#contacts">Contacts <span class="badge badge-light-primary ms-1">{{ $household->contacts->count() }}</span></a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#recipients">Care Recipients <span class="badge badge-light-info ms-1">{{ $household->careRecipients->count() }}</span></a></li>
    </ul>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tab-content">

            {{-- DETAILS --}}
            <div class="tab-pane fade show active" id="details">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr><th class="w-40">Area</th><td>{{ $household->area }}</td></tr>
                                    <tr><th>Language</th><td>{{ $household->primary_language }}</td></tr>
                                    <tr><th>Status</th><td>{!! $household->status_badge !!}</td></tr>
                                    <tr><th>Coordinator</th><td>{{ $household->coordinator->name ?? '-' }}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr><th class="w-40">Access Notes</th><td>{{ $household->access_notes ?? '-' }}</td></tr>
                                    <tr><th>Founding Cohort</th><td>{{ $household->founding_cohort ? 'Yes' : 'No' }}</td></tr>
                                    <tr><th>Created</th><td>{{ $household->created_at->format('d M Y') }}</td></tr>
                                </table>
                            </div>
                        </div>
                        @if ($household->leads->isNotEmpty())
                        <hr>
                        <h6>Linked Leads</h6>
                        <ul>
                            @foreach ($household->leads as $lead)
                            <li><a href="{{ route('leads.show', $lead->id) }}">{{ $lead->full_name }}</a> — {!! $lead->status_badge !!}</li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </div>

            {{-- CONTACTS --}}
            <div class="tab-pane fade" id="contacts">
                <div class="card mb-4">
                    <div class="card-header"><h6 class="card-title mb-0 fw-bold">Add Contact</h6></div>
                    <div class="card-body">
                        <form action="{{ route('households.contacts.store', $household->id) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-3">
                                    <x-form-group>
                                        <x-form-label for="contact_name" required="true">Name</x-form-label>
                                        <input type="text" name="name" class="form-control" maxlength="150" required>
                                    </x-form-group>
                                </div>
                                <div class="col-md-2">
                                    <x-form-group>
                                        <x-form-label for="contact_role" required="true">Role</x-form-label>
                                        <select name="role" class="form-select" required>
                                            @foreach ($contactRoles as $role)
                                                <option value="{{ $role }}">{{ $role }}</option>
                                            @endforeach
                                        </select>
                                    </x-form-group>
                                </div>
                                <div class="col-md-3">
                                    <x-form-group>
                                        <x-form-label for="contact_phone" required="true">Phone</x-form-label>
                                        <input type="text" name="phone" class="form-control" maxlength="20" required>
                                    </x-form-group>
                                </div>
                                <div class="col-md-3">
                                    <x-form-group>
                                        <x-form-label for="contact_email">Email</x-form-label>
                                        <input type="email" name="email" class="form-control" maxlength="254">
                                    </x-form-group>
                                </div>
                                <div class="col-md-1 d-flex align-items-end mb-5">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Add</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        @forelse ($household->contacts as $contact)
                        <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                            <div>
                                <strong>{{ $contact->name }}</strong>
                                <span class="badge badge-light-info ms-2">{{ $contact->role }}</span>
                                <span class="text-muted ms-2">{{ $contact->phone }}</span>
                                @if ($contact->email) <span class="text-muted ms-2">· {{ $contact->email }}</span> @endif
                            </div>
                            <form action="{{ route('households.contacts.destroy', [$household->id, $contact->id]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-light-danger btn-sm"
                                    onclick="return confirm('Delete this contact?')"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        </div>
                        @empty
                        <div class="p-4 text-center text-muted">No contacts added yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- CARE RECIPIENTS --}}
            <div class="tab-pane fade" id="recipients">
                <div class="card mb-4">
                    <div class="card-header"><h6 class="card-title mb-0 fw-bold">Add Care Recipient</h6></div>
                    <div class="card-body">
                        <form action="{{ route('households.care-recipients.store', $household->id) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-3">
                                    <x-form-group>
                                        <x-form-label for="first_name" required="true">First Name</x-form-label>
                                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" maxlength="100" required>
                                        <x-input-error :messages="$errors->get('first_name')" />
                                    </x-form-group>
                                </div>
                                <div class="col-md-2">
                                    <x-form-group>
                                        <x-form-label for="recipient_type" required="true">Type</x-form-label>
                                        <select name="recipient_type" class="form-select" required>
                                            @foreach ($recipientTypes as $type)
                                                <option value="{{ $type }}">{{ $type }}</option>
                                            @endforeach
                                        </select>
                                    </x-form-group>
                                </div>
                                <div class="col-md-2">
                                    <x-form-group>
                                        <x-form-label for="date_of_birth">Date of Birth</x-form-label>
                                        <input type="date" name="date_of_birth" class="form-control">
                                    </x-form-group>
                                </div>
                                <div class="col-md-2">
                                    <x-form-group>
                                        <x-form-label for="age_band">Age Band</x-form-label>
                                        <select name="age_band" class="form-select">
                                            <option value="">— or select —</option>
                                            @foreach ($ageBands as $band)
                                                <option value="{{ $band }}">{{ $band }}</option>
                                            @endforeach
                                        </select>
                                    </x-form-group>
                                </div>
                                <div class="col-md-2">
                                    <x-form-group>
                                        <x-form-label for="relationship">Relationship</x-form-label>
                                        <input type="text" name="relationship" class="form-control" maxlength="100">
                                    </x-form-group>
                                </div>
                                <div class="col-md-1 d-flex align-items-end mb-5">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">Add</button>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('date_of_birth')" />
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <x-form-group>
                                        <x-form-label for="support_notes">Support Notes <small class="text-muted">(non-clinical only)</small></x-form-label>
                                        <textarea name="support_notes" class="form-control @error('support_notes') is-invalid @enderror" rows="2" maxlength="1000"></textarea>
                                        <x-input-error :messages="$errors->get('support_notes')" />
                                    </x-form-group>
                                </div>
                                <div class="col-md-6">
                                    <x-form-group>
                                        <x-form-label for="dietary_restrictions">Dietary Restrictions</x-form-label>
                                        <input type="text" name="dietary_restrictions" class="form-control" maxlength="500">
                                    </x-form-group>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        @forelse ($household->careRecipients as $recipient)
                        <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                            <div>
                                <strong>{{ $recipient->first_name }}</strong>
                                <span class="badge badge-light-{{ $recipient->recipient_type === 'Child' ? 'warning' : 'info' }} ms-2">{{ $recipient->recipient_type }}</span>
                                @if ($recipient->is_sensitive)
                                    <span class="badge badge-light-danger ms-1">Sensitive</span>
                                @endif
                                <span class="text-muted ms-2">
                                    {{ $recipient->date_of_birth ? $recipient->date_of_birth->format('d M Y') : ($recipient->age_band ?? '') }}
                                </span>
                                @if ($recipient->relationship)
                                    <span class="text-muted ms-1">· {{ $recipient->relationship }}</span>
                                @endif
                            </div>
                            <form action="{{ route('households.care-recipients.destroy', [$household->id, $recipient->id]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-light-danger btn-sm"
                                    onclick="return confirm('Delete this care recipient?')"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        </div>
                        @empty
                        <div class="p-4 text-center text-muted">No care recipients added yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
