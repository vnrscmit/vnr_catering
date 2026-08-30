@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">

<link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
@endpush

@push('scripts')

<script src="/admin_resources/vendors/js/vendor.bundle.base.js"></script>
<script src="/admin_resources/js/off-canvas.js"></script>
<script src="/admin_resources/js/hoverable-collapse.js"></script>
<script src="/admin_resources/js/template.js"></script>
<script src="/admin_resources/js/settings.js"></script>
<script src="/admin_resources/js/todolist.js"></script>

<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<script type="text/javascript">
    $(document).ready(function() {

        function setToDateMin() {
            var fromDate = $('#from_date').val();

            if (fromDate) {
                $('#to_date').attr('min', fromDate);

                // If To Date is smaller than From Date
                if ($('#to_date').val() < fromDate) {
                    $('#to_date').val(fromDate);
                }
            }
        }

        // On page load
        setToDateMin();

        // When From Date changes
        $('#from_date').on('change', function() {
            setToDateMin();
        });

    });

    $(document).ready(function() {

        // Initialize DataTable
        var table = $('#guest-table').DataTable({

            processing: true,
            serverSide: true,

            ajax: {
                url: "{{ route('report.guest.data') }}",

                data: function(d) {

                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                    d.event_id = $('#event_filter').val();
                    d.department_id = $('#department_filter').val();

                }
            },

            columns: [

                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'event_name',
                    name: 'event_name'
                },

                {
                    data: 'date',
                    name: 'date'
                },

                {
                    data: 'department_name',
                    name: 'department_name'
                },

                {
                    data: 'guest_name',
                    name: 'guest_name'
                },

                {
                    data: 'guest_count',
                    name: 'guest_count'
                },

                {
                    data: 'guest_remarks',
                    name: 'guest_remarks'
                },
                {
                    data: 'created_by',
                    name: 'created_by'
                }

            ],

            pageLength: 10,

            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],

            order: [
                [1, 'desc']
            ],

            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                zeroRecords: "No records found"
            },

            dom: 'lBfrtip',

            buttons: [
                'excel',
            ]

        });


        // Apply Filters
        $('#filterForm').on('submit', function(e) {

            e.preventDefault();

            table.ajax.reload();

        });


        // Reset Filters
        $('#resetFilters').on('click', function() {

            $('#from_date').val("{{ date('Y-m-d') }}");
            $('#to_date').val("{{ date('Y-m-d') }}");

            $('#event_filter').val('All');
            $('#department_filter').val('All');

            table.ajax.reload();

        });

    });
</script>

@endpush


@section('title', 'Guest Report')

@section('content')

<div class="main-panel">

    <div class="content-wrapper">

        @include('partials.message-bag')

        <div class="card">

            <div class="card-header">

                <h5 class="card-title mb-0">
                    Guest Report -
                    {{ auth()->user()->location->name ?? 'N/A' }}
                </h5>

            </div>

            <div class="card-body">

                {{-- Filter Section --}}
                <form id="filterForm"
                    method="GET"
                    action="{{ route('report.guest') }}">

                    <div class="row">

                        {{-- From Date --}}
                        <div class="col-md-3">
                            <div class="form-group">

                                <label for="from_date">
                                    From Date
                                </label>

                                <input type="date"
                                    class="form-control"
                                    id="from_date"
                                    name="from_date"
                                    value="{{ $fromDate ?? date('Y-m-d') }}">

                            </div>
                        </div>


                        {{-- To Date --}}
                        <div class="col-md-3">
                            <div class="form-group">

                                <label for="to_date">
                                    To Date
                                </label>

                                <input type="date"
                                    class="form-control"
                                    id="to_date"
                                    name="to_date"
                                    value="{{ $toDate ?? date('Y-m-d') }}">

                            </div>
                        </div>


                        {{-- Event --}}
                        <div class="col-md-3">
                            <div class="form-group">

                                <label for="event_filter">
                                    Event
                                </label>

                                <select class="form-control"
                                    id="event_filter"
                                    name="event_id">

                                    <option value="All">
                                        All Events
                                    </option>

                                    @foreach($events as $event)

                                    <option value="{{ $event->event_id }}"
                                        {{ isset($selectedEvent) && $selectedEvent == $event->event_id ? 'selected' : '' }}>

                                        {{ $event->event->name ?? 'N/A' }}

                                    </option>

                                    @endforeach

                                </select>

                            </div>
                        </div>


                        {{-- Department --}}
                        <div class="col-md-3">
                            <div class="form-group">

                                <label for="department_filter">
                                    Department
                                </label>

                                <select class="form-control"
                                    id="department_filter"
                                    name="department_id">

                                    <option value="All">
                                        All Departments
                                    </option>

                                    @foreach($departments as $department)

                                    <option value="{{ $department->department_id }}"
                                        {{ isset($selectedDepartment) && $selectedDepartment == $department->department_id ? 'selected' : '' }}>

                                        {{ $department->department_name ?? 'N/A' }}

                                    </option>

                                    @endforeach

                                </select>

                            </div>
                        </div>

                    </div>

                    {{-- Buttons --}}
                    <div class="d-flex justify-content-end">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Search</button>
                            <button type="button" id="resetFilters" class="btn btn-secondary"><i class="fa fa-undo"></i> Reset</button>
                        </div>
                    </div>

                </form>


                {{-- Table --}}
                <div class="table-responsive mt-3">

                    <table class="table table-bordered"
                        id="guest-table">

                        <thead>

                            <tr>
                                <th>#</th>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Department</th>
                                <th>Guest Name</th>
                                <th>Guest Count</th>
                                <th>Guest Remarks</th>
                                <th>Created By</th>

                            </tr>

                        </thead>

                        <tbody>
                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

    @include('partials.admin.footer')

</div>

@endsection