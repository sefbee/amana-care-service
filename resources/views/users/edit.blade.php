@extends('layouts.base')
@section('title', 'User Edit')
@php
    $breadcrumb = [
        ['title' => 'Users'],
        ['title' => 'Manage', 'url' => route('users.index')],
        ['title' => 'User Edit'],
    ];
@endphp


@section('content')
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <x-alert :show_validations="false" />

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('users.update', ['user' => $user->id]) }}" method="POST" id="user_form" enctype="multipart/form-data">
                        @csrf
                        @method('put')

                        <div class="row">
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label required="true" for="name">Name</x-form-label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        placeholder="Name" value="{{ $user->name }}" required />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </x-form-group>

                                 <x-form-group>
                                    <x-form-label for="phone" required="true">Phone</x-form-label>
                                    <input type="number" name="phone" minlength="11" maxlength="11" id="phone"
                                        class="form-control" placeholder="01XXXXXXXXX" value="{{ $user->phone }}" required/>
                                    <small class="text-info">N.B: 11 digit phone no</small>
                                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                                </x-form-group>

                                <x-form-group>
                                    <x-form-label for="dob" required="true">Date Of Birth</x-form-label>
                                    <input class="form-control form-control-solid" name="dob" placeholder="Pick a date of Birth" id="kt_datepicker_2" value="{{ old('dob', $user->dob) }}" required/>
                                    <x-input-error :messages="$errors->get('dob')" class="mt-2" />
                                </x-form-group>

                            </div>
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label for="designation" required="true">Designation</x-form-label>
                                    <input type="text" name="designation" id="designation" class="form-control"
                                        placeholder="Designation" value="{{ $user->designation }}" required />
                                    <x-input-error :messages="$errors->get('designation')" class="mt-2" />
                                </x-form-group>

                                
                                <x-form-group>
                                    <x-form-label for="email" required="true">Email</x-form-label>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="abc@example.com" value="{{ $user->email }}" required/>
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                    <small class="text-info">N.B: example@sample.com</small>
                                </x-form-group>

    
                                <x-form-group>
                                    <x-form-label for="photo">Photo</x-form-label>
                                    <input type="file" name="photo" id="photo" class="form-control" accept="image/*" />
                                    @if($user->photo)
                                        <div class="mt-2">
                                            <img src="{{ $user->photo_url }}" style="max-width: 120px; max-height: 120px; border-radius: 8px;">
                                        </div>
                                    @endif
                                    <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                                </x-form-group>
                            </div>
                        </div>

                        <div class="mt-4 border-top pt-5">
                            <button type="submit" class="btn btn-primary" id="createBtn">Update</button>
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
            $("#territory_id").select2();
            $("#kt_datepicker_2").flatpickr();

        })
    </script>
@endpush
