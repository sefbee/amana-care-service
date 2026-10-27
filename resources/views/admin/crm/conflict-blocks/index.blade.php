@extends('layouts.base')

@section('title', 'Conflict-of-Interest Blocks')

@section('actions')
    <a href="{{ route('record-conflict-blocks.create') }}" class="btn btn-sm btn-primary">Add Block</a>
@endsection

@section('content')
<div class="row"><div class="col-md-12">
    <div class="card"><div class="card-body">
        @php($columns = [
            ['data' => 'id', 'name' => 'id', 'visible' => false, 'searchable' => false],
            ['data' => 'staff', 'name' => 'staff', 'th' => 'Blocked staff member', 'orderable' => false, 'searchable' => false],
            ['data' => 'record_type', 'name' => 'record_type', 'th' => 'Record type', 'orderable' => false, 'searchable' => false],
            ['data' => 'target', 'name' => 'target', 'th' => 'Record', 'orderable' => false, 'searchable' => false],
            ['data' => 'state', 'name' => 'state', 'th' => 'State', 'orderable' => false, 'searchable' => false],
            ['data' => 'created_at', 'name' => 'created_at', 'th' => 'Applied'],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
        ])
        <x-datatable :columns="$columns" :url="route('record-conflict-blocks.index')" id="record_conflict_blocks_table" />
    </div></div>
</div></div>
@endsection
