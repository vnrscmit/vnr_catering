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

<script>
    $(function() {

        $('.data-table').DataTable({

            processing: true,
            serverSide: true,

            ajax: "{{ route('rate-masters.index') }}",

            columns: [

                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    searchable: false,
                    orderable: false
                },

                {
                    data: 'location',
                    name: 'location_name'
                },


                {
                    data: 'effective_from_date',
                    name: 'effective_from_date'
                },

                {
                    data: 'member_rate',
                    name: 'member_rate'
                },

                {
                    data: 'non_member_rate',
                    name: 'non_member_rate'
                },

                {
                    data: 'guest_rate',
                    name: 'guest_rate'
                },

                {
                    data: 'min_day_rate',
                    name: 'min_day_rate'
                },

                {
                    data: 'feast_day',
                    name: 'feast_day',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'status',
                    name: 'status',
                    searchable: false
                },

                {
                    data: 'action',
                    name: 'action',
                    searchable: false,
                    orderable: false
                }

            ],

            pageLength: 10,

            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],

            order: [
                [2, 'desc']
            ],

            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                zeroRecords: "No records found"
            },

            dom: 'lBfrtip',

            buttons: [
                'excel',
                'pdf'
            ]

        });

    });


    $('#deleteModal').on('show.bs.modal', function(event) {

        let button = $(event.relatedTarget);

        let id = button.data('id');

        let url = "{{ route('rate-masters.destroy',':id') }}";

        url = url.replace(':id', id);

        $('#deleteForm').attr('action', url);

    });
</script>

@endpush

@section('title','Rate Master')

@section('content')

<div class="main-panel">

    <div class="content-wrapper">

        @include('partials.message-bag')

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">

                <h5 class="card-title mb-0">
                    Rate Master - {{ auth()->user()->location->name ?? 'N/A' }}
                </h5>

                <a href="{{ route('rate-masters.create') }}" class="btn btn-primary btn-sm">

                    <i class="fa fa-plus"></i>

                    Add Rate

                </a>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered data-table">

                        <thead>

                            <tr>

                                <th width="5%">#</th>
                                <th>Location</th>
                                <th>Effective Month</th>
                                <th>Member Rate</th>
                                <th>Non Member Rate</th>
                                <th>Guest Rate</th>
                                  <th>Minimum Day Rate</th>
                                <th width="10%">Feast Day</th>
                                <th>Status</th>
                                <th width="15%">Action</th>

                            </tr>

                        </thead>

                    </table>

                </div>

            </div>

        </div>



        {{-- Delete Modal --}}

        <div class="modal fade" id="deleteModal">

            <div class="modal-dialog">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Delete Rate
                        </h5>

                        <button class="btn-close"
                            data-bs-dismiss="modal"></button>

                    </div>

                    <div class="modal-body">

                        Are you sure you want to delete this Rate Master?

                    </div>

                    <div class="modal-footer">

                        <button class="btn btn-secondary"
                            data-bs-dismiss="modal">

                            Cancel

                        </button>

                        <form id="deleteForm"
                            method="POST">

                            @csrf

                            @method('DELETE')

                            <button class="btn btn-danger">

                                Delete

                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

    @include('partials.admin.footer')

</div>

@endsection