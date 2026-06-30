@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">

<!-- DataTables CSS -->
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

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Export Buttons -->
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<script>
    $('#addMenuForm').submit(function(e) {

        e.preventDefault();

        $.ajax({
            url: "{{ route('admin.menus.store') }}",
            type: "POST",
            data: $(this).serialize(),

            success: function(response) {

                $('#addMenuModal').modal('hide');

                $('#addMenuForm')[0].reset();

                table.ajax.reload(null, false);
            },
            error: function(xhr) {

                if (xhr.status == 422) {

                    let errors = xhr.responseJSON.errors;
                    let msg = '';

                    $.each(errors, function(key, value) {
                        msg += value[0] + "\n";
                    });

                    alert(msg);

                }

            }

        });

    });



    var table;

    $(function() {

        table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.menus.index') }}",

            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'name',
                    name: 'name'
                },

                {
                    data: 'submenus',
                    name: 'submenus',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'status',
                    name: 'status'
                },
            
             
            ],

            pageLength: 10,

            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],

            dom: 'lBfrtip',

            buttons: [
                'excel',
                'pdf'
            ],

            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                zeroRecords: "No records found"
            }
        });

    });


</script>
@endpush


@section('title','Menu Master')

@section('content')

<div class="main-panel">
    <div class="content-wrapper">

        @include('partials.message-bag')

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    Menu Master
                </h5>

                <button class="btn btn-primary btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#addMenuModal">
                    <i class="fa fa-plus"></i> Add Menu
                </button>
            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered data-table" id="menus-table">

                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="25%">Menu Name</th>
                                <th width="60%"></thwidth>Sub Menus</th>
                                <th width="10%">Status</th>
                            
                            </tr>

                        </thead>

                    </table>

                </div>

            </div>

        </div>

    </div>

    @include('partials.admin.footer')

</div>

<!-- Delete Modal -->

<div class="modal fade" id="deleteModal" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Confirm Delete
                </h5>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>
            </div>

            <div class="modal-body">

                Are you sure you want to delete this menu?

            </div>

            <div class="modal-footer">

                <button type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">
                    Cancel
                </button>

                <form id="deleteForm" method="POST">

                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        Delete
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<!-- ...............................................Model....................................................... -->

<div class="modal fade" id="addMenuModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="addMenuForm">
              @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Menu</h5>

                    <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Menu Name <span class="text-danger">*</span></label>

                        <input type="text"
                            name="name"
                            class="form-control"
                            placeholder="Enter Menu Name"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>

                        <select name="status" class="form-control">
                            <option value="1" selected>Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">

                    <button type="submit"
                        class="btn btn-primary">
                        Save
                    </button>
                    <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>
                </div>

            </div>

        </form>
    </div>
</div>
@endsection