@extends('layouts.base')
@section('title', 'Admin Console | System Info')

@php
    $breadcrumb = [
        ['title'=> 'Admin Console'],
        ['title'=> 'System Info']
    ]
@endphp

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">System Information</h3>
            </div>

            <div class="card-body">
                <table class="table table-bordered">
                    {{-- <tr>
                        <th>Key</th>
                        <th>Value</th>
                    </tr> --}}
                    @foreach($sys_info as $key => $value)
                        <tr>
                            <td><b>{{ $key }}</b></td>
                            <td>
                                @if(is_array($value))
                                    <pre>{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    {{ $value }}
                                @endif                                
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>

    </div>
</div>
@endsection