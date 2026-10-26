<div class="table-responsive">
    <table class="{{ $class ? $class : 'table table-hover table-row-bordered table-striped gs-5 gy-4 border rounded' }}"
        id="{{ $id }}" style="width: 100%;">
        <thead>
            <tr class="fw-semibold fs-6 text-gray-800">
                @foreach ($columns as $td)
                    <th {{ isset($td['visible']) && $td['visible'] == false ? 'hidden="true"' : '' }}
                        class="{{ strtolower($td['data']) == 'action' ? 'dt-right' : '' }} {{ isset($td['class']) ? $td['class'] : '' }}">
                        {{ isset($td['th']) ? ucfirst($td['th']) : str_replace('_', ' ', ucfirst($td['data'])) }}</th>
                @endforeach
            </tr>
        </thead>
    </table>
</div>

@section('datatable_styles')
    <link href="{{ asset('theme/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />

    <style>
        table.table.dataTable>tbody>tr>td {
            font-size: 1.1rem !important;
        }
    </style>
@endsection

@section('datatable_scripts')
    <script src="{{ asset('theme/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endsection

@php
    $columns = array_map(function ($item) {
        if (strtolower($item['data']) == 'action') {
            $item['searchable'] = false;
            $item['orderable'] = false;
            $item['class'] = 'dt-right';
        }

        return $item;
    }, $columns);
@endphp

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            var searching = '{{ $searching == true ? 'true' : 'false' }}';
            var paging = '{{ $paging == true ? 'true' : 'false' }}';

            var dtTableInitializeObjectValues = {
                "serverSide": true,
                "processing": true,
                // "responsive": true,
                "ajax": '{!! $url !!}',
                "columns": @json($columns),
                "searchDelay": 500,
                "stateSave": false,
                "async": true,
                "sAutoWidth": false, // 'sWidth'=> '20%',
                "order": [
                    [0, "{{ $order }}"]
                ],
                "columnDefs": [{
                    "targets": [-1],
                    "searchable": false,
                    "sortable": false,
                    // className: 'dt-right'
                }],
                "dom": "<'row mb-2'" +
                    "<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                    "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>" +
                    ">" +

                    "<'table-responsive'tr>" +

                    "<'row'" +
                    "<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                    "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>" +
                    ">"
            };
            if (searching == 'false') {
                dtTableInitializeObjectValues.searching = false;
            }

            if (paging == 'false') {
                dtTableInitializeObjectValues.paging = false;
                dtTableInitializeObjectValues.info = false;
            }

            var dtable = $('#{!! $id !!}').DataTable(dtTableInitializeObjectValues);

            $('#{!! $id !!}_filter input')
                .unbind() // Unbind previous default bindings
                .bind("input", function(e) { // Bind our desired behavior
                    // If the length is 3 or more characters, or the user pressed ENTER, search
                    if (this.value.length >= 3 || e.keyCode == 13) {
                        // Call the API search function
                        dtable.search(this.value).draw();
                    }
                    // Ensure we clear the search if they backspace far enough
                    if (this.value == "") {
                        dtable.search("").draw();
                    }
                    return;
                });
        })
    </script>
@endpush
