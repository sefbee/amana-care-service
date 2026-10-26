@props([
    'id' => 'dt-table',
    'columns',
    'class' => '',
    'searching' => true,
    'paging' => true,
])

<div class="table-responsive">
    <table class="{{ $class ? $class : 'table table-hover table-row-bordered table-striped gs-5 gy-4 border rounded' }}"
        id="{{ $id }}" style="width: 100%;">
        {{ $slot }}
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


@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            var searching = '{{ $searching == true ? 'true' : 'false' }}';
            var paging = '{{ $paging == true ? 'true' : 'false' }}';

            var dtTableInitializeObjectValues = {
                // responsive: true,
                // "responsive": true,
                "order": [
                    [0, "desc"]
                ],
                "columnDefs": [{
                    "targets": [-1],
                    "searchable": false,
                    "sortable": false,
                    // className: 'dt-right'
                }],
                "dom":
                    "<'row mb-2'" +
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
        })
    </script>
@endpush
