@extends('layouts.base')
@section('title', 'Admin Console | Menus')

@php
    $breadcrumb = [
        ['title'=> 'Admin Console'],
        ['title'=> 'Menus', 'url' => route('menus.index')]
    ]
@endphp

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <button type="button" class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal"
            data-bs-target="#add_menu_modal">
            New Menu
        </button>
        <div class="card card-flush h-xl-100">
            <div class="card-body">
                <x-table id="menus-table">
                    <thead>
                        <tr class="fw-semibold fs-6 text-gray-800">
                            <th>Title</th>
                            <th>Icon</th>
                            <th>Parent Menu</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($menus as $menu)
                        <tr>
                            <td style="border-left: {{ !$menu->parent_menu_id ? '3px' : '0px' }} solid green;">{{ $menu->title }}</td>
                            <td>
                                <span class="d-flex align-items-center">
                                    @if ($menu->menu_icon)
                                    <i class="ki-outline ki-{{ $menu->menu_icon }} fs-3"></i> &nbsp;
                                    @endif
                                    {{ $menu->menu_icon ?? '-' }}
                                </span>
                            </td>
                            <td>{{ $menu->parent_menu ? $menu->parent_menu->title : '-' }}</td>
                            <td>{!! $menu->status !!}</td>
                            <td>
                                <a href="#" class="btn btn-light-primary btn-icon btn-sm me-2" data-bs-toggle="modal"
                                    data-bs-target="#update_menu_modal_{{ $menu->id }}"><i class="fas fa-pen"></i></a>

                                <button type="button" class="btn btn-sm btn-light-danger btn-icon"
                                    onclick="confirmDelete('menuDelete{{ $menu->id }}')">
                                    <i class="fa fa-times"></i>
                                </button>
                                <form method="POST" action="{{ route('menus.destroy', ['menu' => $menu->id]) }}"
                                    id="menuDelete{{ $menu->id }}">
                                    @csrf
                                    @method('DELETE')
                                </form>


                                <x-modal id="update_menu_modal_{{ $menu->id }}" title="Update Menu">
                                    <form action="{{ route('menus.update', ['menu' => $menu->id]) }}" method="POST"
                                        id="update_form_{{ $menu->id }}">
                                        @method('put')
                                        @csrf
                                        <div class="modal-body">
                                            <div class="form-group mb-4">
                                                <label for="title_{{ $menu->id }}" class="form-label">Title
                                                    *</label>
                                                <input type="text" name="title" id="title_{{ $menu->id }}"
                                                    value="{{ $menu->title }}" class="form-control"
                                                    placeholder="Menu title" required />
                                            </div>

                                            <div class="form-group mb-4">
                                                <label for="route_name_{{ $menu->id }}" class="form-label">Route
                                                    Name </label>
                                                <input type="text" name="route_name" id="route_name_{{ $menu->id }}"
                                                    class="form-control" value="{{ $menu->route_name }}"
                                                    placeholder="Route name" />
                                            </div>

                                            <div class="form-group mb-4">
                                                <label for="menu_icon_{{ $menu->id }}" class="form-label">Menu
                                                    Icon</label>
                                                <input type="text" name="menu_icon" id="menu_icon_{{ $menu->id }}"
                                                    class="form-control" value="{{ $menu->menu_icon }}"
                                                    placeholder="Menu Icon" />
                                            </div>

                                            <div class="form-group mb-4">
                                                <label for="menu_order_{{ $menu->id }}" class="form-label">Menu
                                                    Order</label>
                                                <input type="number" min="1" name="menu_order"
                                                    id="menu_order_{{ $menu->id }}" class="form-control"
                                                    value="{{ $menu->menu_order }}" placeholder="Menu Order" />
                                            </div>

                                            <div class="form-group mb-4">
                                                <label for="parent_menu_id_{{ $menu->id }}" class="form-label">Parent
                                                    Menu</label>
                                                <select name="parent_menu_id" id="parent_menu_id_{{ $menu->id }}"
                                                    class="form-select">
                                                    <option value="">-Select Parent Menu-</option>
                                                    @foreach ($menus as $par_menu)
                                                    <option value="{{ $par_menu->id }}" {{ $menu->parent_menu_id ==
                                                        $par_menu->id ? 'selected' : '' }}>{{ $par_menu->title }}
                                                        {{ $par_menu->sub_menus_count ? '*' : '' }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="is_active_{{ $menu->id }}" class="form-label">Status</label>
                                                <select name="is_active" id="is_active_{{ $menu->id }}"
                                                    class="form-select">
                                                    <option value="1" {{ $menu->is_active == 1 ? 'selected' : ''
                                                        }}>Active</option>
                                                    <option value="0" {{ $menu->is_active == 0 ? 'selected' : ''
                                                        }}>Inactive</option>
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
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </div>
        </div>
    </div>
</div>

<x-modal id="add_menu_modal" title="Add New Menu">
    <form action="{{ route('menus.store') }}" method="POST">
        @csrf
        <div class="modal-body">
            <div class="form-group mb-4">
                <label for="title" class="form-label">Title *</label>
                <input type="text" name="title" id="title" class="form-control" placeholder="Menu title" required />
            </div>

            <div class="form-group mb-4">
                <label for="route_name" class="form-label">Route Name </label>
                <input type="text" name="route_name" id="route_name" class="form-control" placeholder="Route name" />
            </div>

            <div class="form-group mb-4">
                <label for="menu_icon" class="form-label">Menu Icon</label>
                <input type="text" name="menu_icon" id="menu_icon" class="form-control" placeholder="Menu Icon" />

                <span class="form-text">Use an icon name supported by the installed icon font.</span>
            </div>

            <div class="form-group mb-4">
                <label for="menu_order" class="form-label">Menu Order</label>
                <input type="number" min="1" name="menu_order" id="menu_order" class="form-control"
                    placeholder="Menu Order" />
            </div>

            <div class="form-group mb-4">
                <label for="parent_menu_id" class="form-label">Parent Menu</label>
                <select name="parent_menu_id" id="parent_menu_id" class="form-select">
                    <option value="">-Select Parent Menu-</option>
                    @foreach ($menus as $par_menu)
                    <option value="{{ $par_menu->id }}">{{ $par_menu->title }}
                        {{ $par_menu->sub_menus_count ? '*' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="is_active" class="form-label">Status</label>
                <select name="is_active" id="is_active" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
    </form>
</x-modal>
@endsection
