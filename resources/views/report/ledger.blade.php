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

        // Initialize DataTable
        var table = $('#ledger-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('report.ledger.data') }}",
                type: 'GET',
                data: function(d) {
                    d.user_id = $('#user_filter').val();
                    d.search = $('input[type="search"]').val(); // DataTable search
                },
                // Don't load data initially
                deferLoading: 0
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'user',
                    name: 'user'
                },
                {
                    data: 'date',
                    name: 'date'
                },
                {
                    data: 'transaction',
                    name: 'transaction'
                },
                {
                    data: 'due',
                    name: 'due'
                },
                {
                    data: 'paid',
                    name: 'paid'
                },
                {
                    data: 'balance',
                    name: 'balance'
                },
                {
                    data: 'created_by_name',
                    name: 'created_by_name'
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
                infoEmpty: "No data available. Please select a user and click Search.",
                infoFiltered: "(filtered from _MAX_ total entries)",
                zeroRecords: "No records found for selected user",
                emptyTable: "No data available. Please select a user and click Search."
            },
            dom: 'lBfrtip',
            buttons: ['excel']
        });

        // Search button - Manual reload with AJAX
        $('#searchBtn').on('click', function(e) {
            e.preventDefault();
            var userId = $('#user_filter').val();

            if (!userId || userId === '') {
                alert('Please select a user first!');
                return false;
            }

            // Reload DataTable with AJAX
            table.ajax.reload();
        });

        // Reset filter
        $('#resetFilters').on('click', function() {
            $('#user_filter').val('').trigger('change');
            // Clear the table
            table.clear().draw();
        });

        // Also trigger search on Enter key
        $('#user_filter').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#searchBtn').click();
            }
        });

    });
</script>
@endpush

@section('title', 'User Ledger Report')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        @include('partials.message-bag')

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">User Ledger Report - {{ auth()->user()->location->name ?? 'N/A' }}</h5>
            </div>
            <div class="card-body">
                <!-- Filter Section -->
                <form id="filterForm" method="GET" action="{{ route('report.ledger.data') }}">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label for="user_filter" class="form-label">Select User <span class="text-danger">*</span></label>
                                <select class="form-control" id="user_filter" name="user_id">
                                    <option value="">-- Select User --</option>
                                    @foreach($user ?? [] as $u)
                                    <option value="{{ $u->id }}" {{ isset($selectedUser) && $selectedUser == $u->id ? 'selected' : '' }}>
                                        {{ $u->first_name ?? $u->name ?? '' }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">

                            <div class="d-flex" style="padding-top: 24px; gap: 4px;">
                                <button type="button" id="searchBtn" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Search
                                </button>
                                <button type="button" id="resetFilters" class="btn btn-secondary">
                                    <i class="fa fa-undo"></i> Reset
                                </button>
                            </div>

                        </div>
                    </div>
                </form>

                <!-- Table -->
                <div class="table-responsive mt-4">
                    <table class="table table-bordered" id="ledger-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                 <th>User</th>
                                <th>Date</th>
                                <th>Transaction</th>
                                <th>Due</th>
                                <th>Paid</th>
                                <th>Balance <br>(+Due/-Adv)</th>
                                <th>Created By</th>
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