@extends('layouts.base')
@section('title', 'Users')

@php
    $breadcrumb = [
        ['title' => 'Users'],
        ['title' => 'Manage users']
    ];
@endphp

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="mb-2">
                <a href="{{ route('users.create') }}" class="btn btn-sm fw-bold btn-primary">Add New User</a>

                 <button type="button" class="btn btn-sm fw-bold btn-success" data-bs-toggle="modal" data-bs-target="#add_modal">User Bulk Upload</button>
                <button type="button" class="btn btn-sm fw-bold btn-info" data-bs-toggle="modal" data-bs-target="#filter_modal">Filter</button>
            </div>

            <div class="card">
                <div class="card-body">
                    @php
                        $columns = [
                            ['data' => 'id', 'name' => 'id', 'visible'=> false, 'searchable'=> false],
                            ['data' => 'userid', 'name' => 'userid', 'visible'=> false, 'orderable'=> false],
                            ['data' => 'image', 'name' => 'image', 'searchable' => false, 'orderable' => false],
                            ['data' => 'name', 'name' => 'name', 'orderable'=> false],
                            ['data' => 'designation', 'name' => 'designation', 'searchable'=> false, 'orderable'=> false],
                            ['data' => 'phone', 'name' => 'phone', 'orderable'=> false],
                            ['data' => 'role', 'name' => 'user_role', 'searchable'=> false, 'orderable'=> false, 'th' => 'role'],
                            ['data' => 'status', 'name' => 'is_active', 'searchable'=> false],
                            ['data' => 'last_login', 'name' => 'last_login', 'searchable'=> false, 'orderable'=> false],
                            ['data' => 'action', 'name' => 'action'],
                        ];
                        $url = route('users.index');
                    @endphp
                    <x-datatable  :columns="$columns" :url="$url" id="users_table" />
                </div>
            </div>

            <x-modal id="add_modal" title="User Bulk Upload">
                <form action="{{ route('users.bulk-upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <x-form-group>
                            <x-form-label for="user_bulk_excel" required="true">Bulk File</x-form-label>
                            <input type="file" name="user_bulk_excel" id="user_bulk_excel" accept=".xlsx" required class="form-control">
                        </x-form-group>
                        <a href="{{ asset('user_bulk_upload.xlsx') }}">Download user bulk format</a>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </x-modal>

        </div>
    </div>


<x-modal id="filter_modal" title="Filter" size="md">
    <form id="filterForm">
        @csrf
        <div class="modal-body">
            <x-form-group>
                <x-form-label for="filter_role_id">User Role</x-form-label>
                <select id="filter_role_id" class="form-select">
                    <option value="">Select Program</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->title }}</option>
                    @endforeach
                </select>
            </x-form-group>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>
</x-modal>

@endsection

@push('scripts')
    <script>
        $(document).ready(async function() {
           //
        })

        $('#filterForm').submit(function(e) {
            e.preventDefault();

            var filter_role_id = $('#filter_role_id').val();
            // var filter_form_category_id = $('#filter_form_category_id').val();

            var actionUrl = '{{ $url }}' + `?role_id=${filter_role_id}`;

            var dt = $('#users_table').DataTable();
            dt.ajax.url(actionUrl);
            dt.draw();
        })
    </script>
@endpush
