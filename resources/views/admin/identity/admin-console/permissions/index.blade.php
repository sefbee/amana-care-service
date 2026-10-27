@extends('layouts.base')
@section('title', 'Admin Console | Permissions')
@php
    $breadcrumb = [
        ['title'=> 'Admin Console'],
        ['title'=> 'Permissions', 'url' => route('permissions.index')]
    ]
@endphp

@section('content')
    <div class="row">
        <div class="col-md-12">
            {{-- <x-alert /> --}}

            @if (count($permissions_not_exist))
            <div class="alert alert-danger">
                <strong class="mb-2">These permission files doesn't exists:</strong> <br>
                <ul>
                    @foreach ($permissions_not_exist as $p)
                    <li>
                        {{ $p->controller_name }} 
                        <button type="button" class="btn btn-light-danger btn-sm ms-2" data-confirm-delete="notExistsPermDelete_{{ $p->id }}">
                            <i class="fa fa-trash"></i>
                            <form method="POST" action="{{ route('permissions.destroy-not-exists', ['permission' => $p->id]) }}"
                                id="notExistsPermDelete_{{ $p->id }}">
                                @csrf
                                @method('DELETE')
                            </form>
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <button type="button" class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal"
                data-bs-target="#add_perm_modal">
                New Permission Create
            </button>
            <form method="POST" action="{{ route('permissions.sync') }}" class="d-inline" data-confirm="Sync permissions now?">
                @csrf
                <button type="submit" class="btn btn-info btn-sm mb-3">Sync Permission</button>
            </form>
            <div class="card card-flush">
                <div class="card-body">
                    <x-table id="permissions-table">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th>Name</th>
                                <th width="20">Slug</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($permissions as $permission)
                                <tr>
                                    <td>{{ $permission->name }}</td>
                                    <td>{{ $permission->slug }}</td>
                                    <td>{{ $permission->description ?? '-' }}</td>
                                    <td>{!! $permission->status !!}</td>
                                    <td>
                                        <a href="#" class="btn btn-light-primary btn-icon btn-sm me-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#update_menu_modal_{{ $permission->id }}"><i
                                                class="fas fa-pen"></i></a>

                                        <button type="button" class="btn btn-sm btn-light-danger btn-icon"
                                            data-confirm-delete="permDelete{{ $permission->id }}">
                                            <i class="fa fa-trash"></i>
                                            <form method="POST" action="{{ route('permissions.destroy', ['permission' => $permission->id]) }}"
                                                id="permDelete{{ $permission->id }}">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </button>

                                        <x-modal id="update_menu_modal_{{ $permission->id }}" title="Update Permission">
                                            <form action="{{ route('permissions.update', ['permission' => $permission->id]) }}" method="POST">
                                                @method('put')
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="form-group mb-4">
                                                        <label for="name_{{ $permission->id }}" class="form-label">Title
                                                            *</label>
                                                        <input type="text" name="name" id="name_{{ $permission->id }}" value="{{ $permission->name }}"
                                                            class="form-control" placeholder="Permission" required />
                                                    </div>

                                                    <div class="form-group">
                                                        <label for="is_active_{{ $permission->id }}"
                                                            class="form-label">Status</label>
                                                        <select name="is_active" id="is_active_{{ $permission->id }}"
                                                            class="form-select">
                                                            <option value="1" {{ $permission->is_active == 1 ? 'selected' : '' }}>Active</option>
                                                            <option value="0" {{ $permission->is_active == 0 ? 'selected' : '' }}>Inactive</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light"
                                                        data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                                </div>
                                            </form>
                                        </x-modal>

                                        <form method="POST" action="{{ route('permissions.sync-controller-permissions', ['permission'=> $permission->id]) }}" class="d-inline" data-confirm="Sync this controller's permissions?">
                                            @csrf
                                            <button type="submit" class="btn btn-light-info btn-icon btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Sync this controller permissions">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                </div>
            </div>

        </div>
    </div>

    <x-modal id="add_perm_modal" title="Add New Permission">
        <form action="{{ route('permissions.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group mb-4">
                    <label for="name" class="form-label">Title
                        *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        class="form-control" placeholder="Permission name" required />
                </div>

                <div class="form-group">
                    <label for="is_active"
                        class="form-label">Status</label>
                    <select name="is_active" id="is_active"
                        class="form-select">
                        <option value="1" >Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light"
                    data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </x-modal>

@endsection
