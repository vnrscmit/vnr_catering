@extends('layouts.admin')

@push('styles')
<!-- base:css -->
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
<!-- plugin js for this page -->
<script src="/admin_resources/vendors/progressbar.js/progressbar.min.js"></script>
<script src="/admin_resources/vendors/chart.js/Chart.min.js"></script>
<!-- Custom js for this page-->
<script src="/admin_resources/js/dashboard.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<script type="text/javascript">
    $(function() {
        var table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.users.index') }}",
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'full_name',
                    name: 'full_name'
                },

                {
                    data: 'role',
                    name: 'role'
                },

                {
                    data: 'mobile',
                    name: 'mobile'
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'start_date',
                    name: 'start_date'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
            ],
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            order: [
                [1, 'asc']
            ],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                zeroRecords: "No records found",
            },
            dom: 'lBfrtip',
            buttons: [
                'excel',
                'pdf'
            ],
        });
    });

    // Edit User Function
    function editUser(button) {
        let user = $(button).data();
        let actionUrl = "{{ route('admin.users.update', ':id') }}".replace(':id', user.id);
        $('#editUserForm').attr('action', actionUrl);

        $('#editFirstName').val(user.first_name);
        $('#editMiddleName').val(user.middle_name || '');
        $('#editLastName').val(user.last_name);
        $('#editEmail').val(user.email);
        $('#editRole').val(user.role);

        if (user.notice === 'change_password_to_activate_account') {
            $('#banCheckboxDiv').hide();
        } else {
            $('#banCheckboxDiv').show();
            $('#banCheckbox').prop('checked', user.status === 0);
        }
    }

    // View User Modal
    $('#viewUserModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var first_name = button.data('first_name');
        var middle_name = button.data('middle_name');
        var last_name = button.data('last_name');
        var email = button.data('email');
        var role = button.data('role');
        var status = button.data('status');
        var phoneNumber = button.data('phone-number');
        var address = button.data('address');
        var profilePicture = button.data('profile-picture');

        var modal = $(this);
        modal.find('#viewProfilePicture').attr('src', profilePicture || "{{ asset('assets/images/user-icon.png') }}");
        modal.find('#viewFirstName').text(first_name);
        modal.find('#viewMiddleName').text(middle_name);
        modal.find('#viewLastName').text(last_name);
        modal.find('#viewEmail').text(email);
        modal.find('#viewRole').text(role);
        modal.find('#viewStatus').html(status === 1 ?
            '<span class="badge bg-primary"><i class="fa fa-check"></i> Active</span>' :
            '<span class="badge bg-danger"><i class="fa fa-exclamation"></i> Inactive</span>');
        modal.find('#viewPhoneNumber').text(phoneNumber || 'N/A');
        modal.find('#viewAddress').text(address || 'N/A');
    });

    // Update Date Modal
    $('#users-table').on('click', '.update-date-btn', function() {
        let id = $(this).data('id');
        $('#user_id').val(id);

    });

    $('#users-table').on('click', '.suspend-user-btn', function() {
        let id = $(this).data('id');
        $('#suspend_user_id').val(id);

    });



    // Delete confirmation
    $(document).ready(function() {
        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var actionUrl = "{{ route('admin.users.destroy', ':id') }}".replace(':id', id);
            $('#deleteForm').attr('action', actionUrl);
        });
    });
</script>
@endpush

@section('title', 'Admin - Master Users')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        @include('partials.message-bag')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Master Users</span>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> Add User
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" id="users-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Start Date</th>
                                <th>Status</th>
                                <th width="18%">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>



        <!-- Update Date Modal -->
        <div class="modal fade" id="updateDateModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('admin.users.updateDate') }}" method="POST">
                        @csrf
                        <input id="user_id" type="hidden" name="user_id">
                        <div class="modal-header">
                            <h5 class="modal-title">Update Date</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>Select Date</label>
                                <input type="date" name="date" class="form-control" min="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-calendar-plus-o"></i> Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Suspend User Modal -->
        <div class="modal fade" id="suspendUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('admin.users.suspend') }}" method="POST">
                        @csrf

                        <input type="hidden" id="suspend_user_id" name="user_id">

                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fa fa-ban"></i> Inactive User
                            </h5>
                            <button type="button" class="btn-close btn-close-white"
                                data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">

                            <div class="mb-3">
                                <label class="form-label">
                                    Inactive Date <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                    name="suspend_date"
                                    class="form-control"
                                    value="{{ date('Y-m-d') }}"
                                    readonly
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Reason <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    name="suspend_remarks"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Enter Inactive reason..."
                                    required></textarea>
                            </div>

                        </div>

                        <div class="modal-footer">
                            <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                                Cancel
                            </button>

                            <button type="submit"
                                class="btn btn-primary">
                                <i class="fa fa-ban"></i> Inactive
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" id="editUserForm">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>First Name</label>
                                <input type="text" name="first_name" id="editFirstName" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" id="editMiddleName" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Last Name</label>
                                <input type="text" name="last_name" id="editLastName" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" id="editEmail" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Role</label>
                                <select name="role" id="editRole" class="form-control form-control-sm form-select" required>
                                    <option value="admin">Admin</option>
                                    <option value="Super Admin">Global Admin</option>
                                </select>
                            </div>
                            <div class="form-check form-check-flat form-check-primary" id="banCheckboxDiv">
                                <label class="form-check-label" for="banCheckbox">
                                    <input type="checkbox" class="form-check-input" id="banCheckbox" name="ban"> Ban User
                                    <i class="input-helper"></i>
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this user?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form id="deleteForm" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- content-wrapper ends -->
    @include('partials.admin.footer')
</div>
<!-- main-panel ends -->
@endsection