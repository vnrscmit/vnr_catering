@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
@endpush

@push('scripts')
<script src="/admin_resources/vendors/js/vendor.bundle.base.js"></script>
<script src="/admin_resources/js/off-canvas.js"></script>
<script src="/admin_resources/js/hoverable-collapse.js"></script>
<script src="/admin_resources/js/template.js"></script>
<script src="/admin_resources/js/settings.js"></script>
<script src="/admin_resources/js/todolist.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        let rowCounter = 0;

        // Function to generate a single row HTML
        function generateRow() {
            return `
                <tr>
                    <td>
                        <input type="text"
                               name="guest_name[]"
                               class="form-control"
                               placeholder="Enter Guest Name"
                               >
                    </td>
                    <td>
                        <select class="form-control" name="guest_department_id[]" required>
                            <option value="0" selected>Select Department</option>
                            @foreach($departments as $department)
                            <option value="{{ $department->id }}">
                                {{ $department->name }}
                            </option>
                            @endforeach
                        </select>
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
        }

        // Function to generate first row (without delete button)
        function generateFirstRow() {
            return `
                <tr>
                    <td>
                        <input type="text"
                               name="guest_name[]"
                               class="form-control"
                               placeholder="Enter Guest Name"
                               >
                    </td>
                    <td>
                        <select class="form-control" name="guest_department_id[]" required>
                            <option value="0" selected>Select Department</option>
                            @foreach($departments as $department)
                            <option value="{{ $department->id }}">
                                {{ $department->name }}
                            </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <button type="button"
                                class="btn btn-primary btn-sm addRowBtn">
                            <i class="fa fa-plus"></i>
                        </button>
                    </td>
                </tr>
            `;
        }

        // Function to set rows based on count
        function setRows(count) {
            let tbody = $('#guestTable tbody');
            tbody.empty();

            if (count < 1) count = 1;

            // Add first row (without delete button)
            tbody.append(generateFirstRow());

            // Add remaining rows (with delete button)
            for (let i = 1; i < count; i++) {
                tbody.append(generateRow());
            }

            updateGuestCount();
        }

        // Guest count change event
        $('#guest_count').on('change keyup input', function() {
            let count = parseInt($(this).val());
            if (!isNaN(count) && count > 0) {
                setRows(count);
            } else {
                $(this).val(1);
                setRows(1);
            }
        });

        // Add row functionality
        $('#addRow').click(function() {
            let row = generateRow();
            $('#guestTable tbody').append(row);
            updateGuestCount();
        });

        // Remove row functionality
        $(document).on('click', '.removeRow', function() {
            if ($('#guestTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
                updateGuestCount();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Error',
                    text: 'You must have at least one row!',
                    confirmButtonText: 'OK'
                });
            }
        });

        // Add row from within row
        $(document).on('click', '.addRowBtn', function() {
            let row = generateRow();
            $(this).closest('tr').after(row);
            updateGuestCount();
        });

        // Update guest count
        function updateGuestCount() {
            let rowCount = $('#guestTable tbody tr').length;
            $('#guest_count').val(rowCount);
        }

        // Initialize on page load
        let initialCount = parseInt($('#guest_count').val()) || 1;
        setRows(initialCount);
    });
</script>
@endpush

@section('title', 'Add Guest')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Create New Guest - {{ auth()->user()->location->name ?? 'N/A' }} </h5>
            </div>
            <div class="card-body">
                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fa fa-exclamation-circle me-2"></i>
                    {{ session('error') }}

                    <button type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"></button>
                </div>
                @endif
                <form action="{{ route('admin.guests.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="calendar_id" value="{{ $dayStatus->id }}">
                    <div class="row">

                        <!-- Location -->
                        <input type="hidden" value="{{ $locationId }}" name="location_id">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Event <span class="text-danger">*</span>
                            </label>
                            <select name="event_id" class="form-control" required>
                                <option value="">Select Event</option>
                                @foreach($eventList as $eventId => $eventName)
                                <option value="{{ $eventId }}">
                                    {{ $eventName }}
                                </option>
                                @endforeach
                            </select>
                            @error('event_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Date -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Date <span class="text-danger">*</span>
                            </label>

                            <input type="date"
                                class="form-control @error('date') is-invalid @enderror"
                                name="date"
                                value="{{ old('date', date('Y-m-d')) }}"
                                min="{{ date('Y-m-d') }}"
                                required>

                            @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Department -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Department
                            </label>

                            <select class="form-control @error('department') is-invalid @enderror"
                                name="department_id">
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department') == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                                @endforeach
                            </select>

                            @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Employee -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Host Employee
                            </label>

                            <select name="attend_user_id"
                                class="form-control @error('attend_user_id') is-invalid @enderror">
                                <option value="">Select Employee</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ old('attend_user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->first_name }}
                                </option>
                                @endforeach

                            </select>

                            @error('attend_user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>



                        <!-- Guest Type -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Guest Type<span class="text-danger">*</span>
                            </label>

                            <div class="d-flex mt-2">

                                <div class="form-check me-4">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="guest_type"
                                        value="Office Guest"
                                        checked>

                                    <label class="form-check-label">
                                        Official
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="guest_type"
                                        value="Personal Guest">

                                    <label class="form-check-label">
                                        Personal
                                    </label>
                                </div>

                            </div>
                        </div>

                        <!-- Guest Count - Input -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Guest Count <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                id="guest_count"
                                class="form-control @error('guest_count') is-invalid @enderror"
                                name="guest_count"
                                value="{{ old('guest_count',1) }}"
                                min="1">
                            <small class="text-muted">Enter number of guests to auto-generate rows</small>
                            @error('guest_count')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Guest Table -->
                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <table class="table table-bordered table-striped" id="guestTable">
                                        <thead style="background-color:#F7F7F7;">
                                            <tr>
                                                <th>Guest Name</th>
                                                <th>Department</th>
                                                <th width="150">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Rows will be generated dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="col-md-12 mb-3">
                            <label>Remarks</label>
                            <textarea
                                name="guest_remarks" placeholder="Enter Remarks" rows="5"
                                class="form-control">{{ old('guest_remarks') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Submit
                            </button>
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
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