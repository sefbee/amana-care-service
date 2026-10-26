@extends('layouts.base')
@section('title', 'Admin Console | User Roles')
@php
    $breadcrumb = [
        ['title'=> 'Admin Console'],
        ['title'=> 'User Roles', 'url' => route('user-roles.index')]
    ]
@endphp

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <x-alert />

        <button type="button" class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal"
            data-bs-target="#add_role_modal">
            New User Role Create
        </button>
        <div class="card card-flush h-xl-100">
            <div class="card-body">
                <x-table id="user-types-table">
                    <thead>
                        <tr class="fw-semibold fs-6 text-gray-800">
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td>{{ $role->title }}</td>
                                <td>{{ $role->slug }}</td>
                                <td>
                                    <a href="{{ route('user-roles.config', ['user_role'=> $role->id]) }}" class="btn btn-light-info btn-icon btn-sm me-2">
                                        <i class="fas fa-cog"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-light-danger btn-icon"
                                        onclick="confirmDelete('permDelete{{ $role->id }}')">
                                        <i class="fa fa-times"></i>
                                        <form method="POST" action="{{ route('user-roles.destroy', ['user_role' => $role->id]) }}"
                                            id="permDelete{{ $role->id }}">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </button>
                                </td>
                            </tr>


                        @endforeach
                    </tbody>
                </x-table>
            </div>
        </div>

    </div>
</div>
<x-modal id="add_role_modal" title="Add User Role">
    <form action="{{ route('user-roles.store') }}" method="POST">
        @csrf
        <div class="modal-body">
            <x-form-group>
                <x-form-label for="title" required="true">Title</x-form-label>
                <input type="text" name="title" id="title" value="{{ old('title') }}"
                    class="form-control" placeholder="User Type Name" required />
            </x-form-group>

            <x-form-group>
                <x-form-label for="is_active" required="true">Status</x-form-label>
                <select name="is_active" id="is_active"
                    class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </x-form-group>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-light"
                data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
    </form>
</x-modal>

@endsection
