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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script type="text/javascript">
    $(document).ready(function() {

        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            applyFilters();
        });

        $('#resetFilters').on('click', function() {
            $('#filterForm')[0].reset();
            $('#date_filter').val('{{ date('Y-m-d') }}');
            $('#user_filter').val('All').trigger('change');
            $('#event_filter').val('All').trigger('change');
            $('#attendance_filter').val('All').trigger('change');
            applyFilters();
        });

        function applyFilters() {
            var date = $('#date_filter').val();
            var userId = $('#user_filter').val();
            var eventId = $('#event_filter').val();
            var attendanceStatus = $('#attendance_filter').val();

            $('#attendance-table').DataTable().ajax.url(
                "{{ route('report.daily.data') }}?date=" + date +
                "&user_id=" + userId +
                "&event_id=" + eventId +
                "&attendance_status=" + attendanceStatus
            ).load();
        }

        $('#attendance-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('report.daily.data') }}",
                data: function(d) {
                    d.date = $('#date_filter').val();
                    d.user_id = $('#user_filter').val();
                    d.event_id = $('#event_filter').val();
                    d.attendance_status = $('#attendance_filter').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'attendance_date',
                    name: 'attendance_date'
                },
                {
                    data: 'event_name',
                    name: 'event_name'
                },
                {
                    data: 'user_name',
                    name: 'user_name'
                },
                {
                    data: 'type',
                    name: 'type'
                },
                {
                    data: 'status_badge',
                    name: 'status',
                    orderable: false,
                    searchable: false
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
            buttons: ['excel']
        });
    });
</script>
@endpush

@section('title', 'Daily Attendance Report')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        @include('partials.message-bag')

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Daily Attendance Report - {{ auth()->user()->location->name ?? 'N/A' }}</h5>
            </div>
            <div class="card-body">
                <!-- Filter Section -->
                <form id="filterForm" method="GET" action="{{ route('report.daily') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Date</label>
                                <input type="date"
                                    class="form-control"
                                    id="date_filter"
                                    name="date"
                                    value="{{ $selectedDate ?? date('Y-m-d') }}"
                                    placeholder="Select Date">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>User</label>
                                <select class="form-control" id="user_filter" name="user_id">
                                    <option value="All">All Users</option>
                                    @foreach($user as $u)
                                    <option value="{{ $u->id }}" {{ isset($selectedUser) && $selectedUser == $u->id ? 'selected' : '' }}>
                                        {{ $u->first_name ?? ''}}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Event</label>
                                <select class="form-control" id="event_filter" name="event_id">
                                    <option value="All">All Events</option>
                                    @foreach($events as $event)
                                    <option value="{{ $event->event_id }}" {{ isset($selectedEvent) && $selectedEvent == $event->event_id ? 'selected' : '' }}>
                                        {{ $event->event->name ?? 'N/A' }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Present/Absent</label>
                                <select class="form-control" id="attendance_filter" name="attendance_status">
                                    <option value="All">All</option>
                                    <option value="Present">Present</option>
                                    <option value="Absent">Absent</option>
                                </select>
                            </div>
                        </div>
                      
                    </div>

                      <div class="d-flex justify-content-end">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Search</button>
                                <button type="button" id="resetFilters" class="btn btn-secondary"><i class="fa fa-undo"></i> Reset</button>
                            </div>
                        </div>
                </form>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered" id="attendance-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Event</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('partials.admin.footer')
</div>

@endsection