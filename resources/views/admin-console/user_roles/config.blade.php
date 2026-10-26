@extends('layouts.base')

@section('title', 'Admin Console | User Roles')

@php
    $breadcrumb = [
        ['title'=> 'Admin Console'],
        ['title'=> 'User Roles', 'url' => route('user-roles.index')],
        ['title'=> 'Config'],
    ]
@endphp

@section('content')
    <div class="row">
        <div class="col-md-8 offset-md-2">

            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">User Role Config</h3>
                    <div class="card-toolbar">
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('user-roles.update', ['user_role' => $userRole->id]) }}" method="POST">
                        @csrf
                        @method('put')
                        <x-form-group>
                            <x-form-label for="title" required="true">Title</x-form-label>
                            <input type="text" name="title" id="title" value="{{ $userRole->title }}"
                                class="form-control" placeholder="User type name" required />
                        </x-form-group>

                        <x-form-group>
                            <x-form-label for="is_active" required="true">Title</x-form-label>
                            <select name="is_active" id="is_active" class="form-select" required>
                                <option value="1" {{ $userRole->is_active == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ $userRole->is_active == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </x-form-group>
                        <button type="submit" class="btn btn-sm fw-bold btn-primary">Update</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-8">
                <div class="card-header">
                    <h3 class="card-title">Web menus for ({{ $userRole->title }})</h3>
                    <div class="card-toolbar">
                        <label for="role_menus_check_all" class="ml-3">
                            <input type="checkbox" name="role_menus_check_all" id="role_menus_check_all"
                                onclick="for(c in document.getElementsByName('role_menus[]')) document.getElementsByName('role_menus[]').item(c).checked = this.checked">
                            Check All
                        </label>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('user-roles.update-menus', ['user_role' => $userRole->id]) }}" method="POST">
                        @csrf
                        @method('put')
                        <div class="">
                            @foreach ($menus as $menu)
                                @if (!$menu->parent_menu_id)
                                    <div class="mb-3">
                                        <label for="role_menu{{ $menu->id }}" class="mb-2">
                                            <input type="checkbox" name="role_menus[]" value="{{ $menu->id }}"
                                                id="role_menu{{ $menu->id }}"
                                                {{ $user_type_menus->contains($menu->id) ? 'checked' : '' }}>
                                            {{ $menu->title }}
                                        </label>
                                        @foreach ($menus as $ch_menu)
                                            @if ($ch_menu->parent_menu_id == $menu->id)
                                                <div class="ms-8 mb-2">
                                                    <label for="role_menu{{ $ch_menu->id }}">
                                                        <input type="checkbox" name="role_menus[]"
                                                            value="{{ $ch_menu->id }}" id="role_menu{{ $ch_menu->id }}"
                                                            {{ $user_type_menus->contains($ch_menu->id) ? 'checked' : '' }}>
                                                        {{ $ch_menu->title }}
                                                    </label>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <div class="">
                            <button type="submit" class="btn btn-sm fw-bold btn-primary mt-3">Save</button>
                        </div>
                    </form>
                </div>
            </div>


            <div class="card shadow-sm mt-8">
                <div class="card-header">
                    <h3 class="card-title">Permissions for ({{ $userRole->title }})</h3>
                    <div class="card-toolbar">
                        <label for="role_permission_check_all" class="ml-3">
                            <input type="checkbox" name="role_permission_check_all" id="role_permission_check_all"
                                onclick="for(c in document.getElementsByName('role_permissions[]')) document.getElementsByName('role_permissions[]').item(c).checked = this.checked">
                            Check All
                        </label>
                    </div>
                </div>

                <div class="card-body">

                    <form action="{{ route('user-roles.update-permissions', ['user_role' => $userRole->id]) }}"
                        method="POST">
                        @csrf
                        @method('put')
                        <div class="">

                            <div class="row">
                                @foreach ($permissions as $controller => $perm_arr)
                                    <div class="col-12 mb-3">
                                        <b>* <span style="text-decoration: underline;">{{ $controller }}:</span></b>

                                        <div class="row mt-2 mb-3 ms-5">
                                            @foreach ($perm_arr as $perm)
                                                <div class="col-4">
                                                    <label for="role_permission{{ $perm->id }}" class="mb-3">
                                                        <input type="checkbox" name="role_permissions[]"
                                                            value="{{ $perm->id }}"
                                                            id="role_permission{{ $perm->id }}"
                                                            {{ $user_type_permissions->contains($perm->id) ? 'checked' : '' }}>
                                                        {{ ucfirst(\Str::of($perm->name)->explode('@')[1] ?? \Str::of($perm->name)->explode('@')[0]) }}

                                                        @if ($perm->description)
                                                        <p class="text-primary">-> {{ $perm->description }}</p>
                                                        @endif
                                                    </label>

                                                </div>
                                            @endforeach
                                        </div>

                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="block-content block-content-sm block-content-full bg-body-light">
                            <button type="submit" class="btn btn-sm fw-bold btn-primary mt-3">Save</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>


@endsection
