@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
                       placeholder="Enter Sub Menu Name"
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
                alert('You must have at least one row!');
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
                       placeholder="Enter Sub Menu Name"
                       required>
            </td>
            <td>
                <button type="button"
                        class="btn btn-info btn-sm addRowBtn">
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

    });
</script>
@endpush


@section('content')

<div class="main-panel">
    <div class="content-wrapper">

        <div class="card mb-4">

            <div class="card-header">
                <h5 class="mb-0">Item Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Menu Category</th>
                            <th>Items</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menu->subMenus as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $menu->name }}</td>
                            <td>{{ $row->name }}</td>
                        </tr>

                        @empty

                        <tr>
                            <td colspan="3" class="text-center">
                                No Location Linked
                            </td>
                        </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>

        </div>


        <div class="card">

            <div class="card-header d-flex justify-content-between">

                <h5>Add Items</h5>

            </div>

            <div class="card-body">

                <form action="{{ route('admin.submenus.store') }}" method="POST">
                    @csrf
                    <input type="hidden"
                        name="menu_id"
                        value="{{ $menu->id }}">

                    <table class="table table-bordered"
                        id="submenuTable">

                        <thead>

                            <tr>
                                <th>Item Name</th>
                                <th>Special Item</th>
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
                                        placeholder="Enter Item Name"
                                        required>
                                </td>
                                <td>
                                 
                                  <input type="checkbox"
       name="special_items[]"
       class="form-check-input"
       style="width:22px !important; height:22px !important; transform:scale(1.3) !important; cursor:pointer !important;">
                                    <!-- No delete button in first row -->
                                </td>
                                <td>
                                       <button type="button"
                                        class="btn btn-info btn-sm addRowBtn">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>

                    </table>

                    <div class="d-flex justify-content-end  mt-2">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                Submit
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

@endsection