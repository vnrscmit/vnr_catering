@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">

@endpush

@push('scripts')
<!-- Load jQuery first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Load Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {

        // Add row functionality
        $('#addRow').click(function() {
            let row = `
        <tr>
            <td>
                <input type="text"
                       name="submenu_name[]"
                       class="form-control"
                       placeholder="Enter Menu Item Name"
                       required>
            </td>
            <td>
                <button type="button"
                        class="btn btn-success btn-sm addRowBtn"
                        id="addRow">
                    <i class="fa fa-plus"></i> Add
                </button>
                <button type="button" 
                        class="btn btn-danger btn-sm removeRow">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
        `;
            $('#submenuTable tbody').append(row);
        });

        // Remove row functionality
        $(document).on('click', '.removeRow', function() {
            // Check if this is the last row
            if ($('#submenuTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Error',
                    text: 'You must have at least one row!',
                    confirmButtonText: 'OK'
                });
                return;
            }
        });

        // Add row from within row
        $(document).on('click', '.addRowBtn', function() {
            let row = `
        <tr>
            <td>
                <input type="text"
                       name="submenu_name[]"
                       class="form-control"
                       placeholder="Enter Menu Item Name"
                       required>
            </td>
            <td>
                <button type="button"
                        class="btn btn-primary btn-sm addRowBtn">
                    <i class="fa fa-plus"></i>
                </button>
                <button type="button" 
                        class="btn btn-danger btn-sm removeRow">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
        `;
            $(this).closest('tr').after(row);
        });

        // Edit Sub Menu - Using Bootstrap 5 modal API
        $(document).on('click', '.editSubMenuBtn', function() {
            let id = $(this).data('id');
            let menu_id = $(this).data('menu_id');
            let name = $(this).data('name');
            let status = $(this).data('status');

            // Set form values
            $('#edit_submenu_id').val(id);
            $('#edit_menu_id').val(menu_id);
            $('#edit_submenu_name').val(name);
            $('#edit_submenu_status').val(status);

            // Set form action with the correct route
            let actionUrl = "{{ route('admin.submenus.update', '') }}/" + id;
            $('#editSubMenuForm').attr('action', actionUrl);

            // Show the modal using Bootstrap 5 API
            var editModal = new bootstrap.Modal(document.getElementById('editSubMenuModal'));
            editModal.show();
        });

        // Optional: Handle form submission via AJAX
        $(document).on('submit', '#editSubMenuForm', function(e) {
            e.preventDefault();

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    // Hide modal
                    var editModal = bootstrap.Modal.getInstance(document.getElementById('editSubMenuModal'));
                    editModal.hide();

                    // Show success message

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Sub menu updated successfully!',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });
                },
                error: function(xhr) {
                    // Handle errors
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        let errorMessage = '';
                        $.each(errors, function(key, value) {
                            errorMessage += value[0] + '\n';
                        });
                        alert('Validation Error:\n' + errorMessage);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong!',
                            confirmButtonText: 'OK'
                        });
                    }
                }
            });
        });

    });
</script>
@endpush

@section('content')

<div class="main-panel">
    <div class="content-wrapper">

        <!-- Item Details Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Menu Item Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead style="background-color:#F7F7F7;">
                        <tr>
                            <th>#</th>
                            <th>Menu Section</th>
                            <th>Menu Items</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menu->subMenus as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $menu->name }}</td>
                            <td>{{ $row->name }}</td>
                            <td>
                                @if($row->status == 1)
                                <span class="badge bg-primary">Active</span>
                                @else
                                <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>

                                <button type="button"
                                    class="btn btn-warning btn-sm editSubMenuBtn"
                                    data-id="{{ $row->id }}"
                                    data-menu_id="{{ $menu->id }}"
                                    data-name="{{ $row->name }}"
                                    data-status="{{ $row->status }}">
                                    <i class="fa fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">
                                No Items Linked
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Items Section -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-1">Add Menu Items</h5>
                <h6 class="mt-2">Menu Section - {{ $menu->name }}</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.submenus.store') }}" method="POST">
                    @csrf
                    <input type="hidden"
                        name="menu_id"
                        value="{{ $menu->id }}">

                    <table class="table table-bordered table-striped" id="submenuTable">
                        <thead style="background-color:#F7F7F7;">
                            <tr>
                                <th>Menu Item Name</th>
                                <th width="150">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- First row - No delete button -->
                            <tr>
                                <td>
                                    <input type="text"
                                        name="submenu_name[]"
                                        class="form-control"
                                        placeholder="Enter Menu Item Name"
                                        required>
                                </td>
                                <td>
                                    <button type="button"
                                        class="btn btn-primary btn-sm addRowBtn">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-end mt-2">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Submit
                            </button>
                            <a href="{{ route('admin.menus.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editSubMenuModal" tabindex="-1" aria-labelledby="editSubMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editSubMenuForm" method="POST">
            @csrf
            @method('patch')

            <input type="hidden" name="id" id="edit_submenu_id">
            <input type="hidden"
                name="menu_id"
                id="edit_menu_id">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubMenuModalLabel">Edit Sub Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_submenu_name" class="form-label">Sub Menu Name</label>
                        <input type="text"
                            class="form-control"
                            id="edit_submenu_name"
                            name="name"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_submenu_status" class="form-label">Status</label>
                        <select class="form-control" id="edit_submenu_status" name="status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection