@extends('layouts.base')
@section('title', 'User Create')

@php
    $breadcrumb = [
        ['title' => 'Users'],
        ['title' => 'Manage', 'url' => route('users.index')],
        ['title' => 'User Create']
    ];
@endphp

@section('content')
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <x-alert :show_validations="false" />

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('users.store') }}" method="POST" id="user_form" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label required="true" for="name">Name</x-form-label>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Name" value="{{ old('name') }}" required />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </x-form-group>
                            </div>
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label for="designation" required="true">Designation</x-form-label>
                                    <input type="text" name="designation" id="designation" class="form-control" placeholder="Designation" value="{{ old('designation') }}" required />
                                    <x-input-error :messages="$errors->get('designation')" class="mt-2" />
                                </x-form-group>
                            </div>

                            
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label required="true" for="userid">Employee ID <small>(Login ID)</small></x-form-label>
                                    <input type="text" name="userid" id="userid" class="form-control" placeholder="XXXX" value="{{ old('userid') }}" required />
                                    <x-input-error :messages="$errors->get('userid')" class="mt-2" />
                                </x-form-group>
                            </div>
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label for="phone" required="true">Phone</x-form-label>
                                    <input type="number" name="phone" minlength="11" maxlength="11"  id="phone" class="form-control" placeholder="01XXXXXXXXX" value="{{ old('phone') }}" required/>
                                    <small class="text-info">N.B: 11 digit phone no</small>
                                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                                </x-form-group>
                            </div>

                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label for="email" required="true">Email</x-form-label>
                                    <input type="email" name="email"  id="email" class="form-control" placeholder="abc@example.com" value="{{ old('email') }}" required/>
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </x-form-group>
                            </div>
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label for="dob" required="true">Date Of Birth</x-form-label>
                                    <input class="form-control form-control-solid" name="dob" placeholder="Pick a date of Birth" id="kt_datepicker_2" value="{{ old('dob') }}" required/>
                                    <x-input-error :messages="$errors->get('dob')" class="mt-2" />
                                </x-form-group>
                            </div>

                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label required="true" for="gender">Gender</x-form-label>
                                    <select name="gender" id="gender" class="form-select" required>
                                        <option value="">Select Gender</option>
                                        <option value="1" {{ old('gender') == 1 ? 'selected' : '' }}>Male</option>
                                        <option value="2" {{ old('gender') == 2 ? 'selected' : '' }}>Female</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                                </x-form-group>
                            </div>

                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label for="primary_role_id" required="true">User Role</x-form-label>
                                    <select name="primary_role_id" id="primary_role_id" class="form-select" required>
                                        <option value="">-Select User Role-</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}" data-role-slug="{{ $role->slug }}"
                                                    {{ selected($role->id, old('primary_role_id')) }}>
                                                {{ $role->title }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('primary_role_id')" class="mt-2" />
                                </x-form-group>
                            </div>
                        </div>

                        <div class="mt-4 border-top pt-5">
                            <button type="submit" class="btn btn-primary" id="createBtn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(async function() {
            $("#primary_role_id").select2();
            $("#kt_datepicker_2").flatpickr();

        })
    </script>
@endpush
