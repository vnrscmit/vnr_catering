@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="/admin_resources/vendors/select2/select2.min.css">
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
<script src="/admin_resources/vendors/select2/select2.min.js"></script>
<script>
    $(function() {

        $('#department_id').on('change', function() {

            let departmentId = $(this).val();

            $('#location_id').html(
                '<option value="">Select Location</option>'
            );

            if (departmentId) {

                $.ajax({
                    url: "{{ url('admin/get-location') }}/" + departmentId,
                    type: "GET",
                    dataType: "json",
                    success: function(response) {

                        $.each(response, function(index, location) {

                            $('#location_id').append(
                                '<option value="' + location.location_id + '">' +
                                location.location_name +
                                '</option>'
                            );
                        });
                    }
                });
            }
        });

        function toggleGuestFields() {
            const guestAllowed = $('input[name="personal_guest_flag"]:checked').val();

            if (guestAllowed == '1') {
                $('#guest_limits_wrapper').slideDown();
            } else {
                $('#guest_limits_wrapper').slideUp();

                // Optional: reset values when "No" is selected
                $('#max_personal_guest_allowed').val(0);
                $('#max_office_guest_allowed').val(0);
            }
        }

        // Trigger on both radio buttons
        $('input[name="personal_guest_flag"]').on('change', toggleGuestFields);

        // Run on page load
        toggleGuestFields();

    });

    $(function() {
        $('.select2').select2({
            placeholder: 'Select Locations',
            allowClear: true
        });
    });
</script>
@endpush


@section('title', 'Create User')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <h5>Create New User</h5>
            </div>
            <div class="card-body">

                <!-- ERROR MESSAGES - TOP -->
                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fa fa-warning"></i> {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa fa-exclamation-circle"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <!-- Role Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-control @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('role_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- First Name -->
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                            @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Mobile -->
                        <div class="col-md-6 mb-3">
                            <label for="mobile" class="form-label">Mobile <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('mobile') is-invalid @enderror" id="mobile" name="mobile" value="{{ old('mobile') }}" placeholder="e.g., 9876543210" required>
                            @error('mobile')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Designation -->
                        <div class="col-md-6 mb-3">
                            <label for="designation" class="form-label">Designation</label>
                            <input type="text" class="form-control @error('designation') is-invalid @enderror" id="designation" name="designation" value="{{ old('designation') }}" placeholder="e.g., Senior Developer">
                            @error('designation')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Department Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-control" id="department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department_id_id') == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                        <!-- Location Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label for="location_id" class="form-label">Base Location <span class="text-danger">*</span></label>
                            <select class="form-control @error('location_id') is-invalid @enderror" id="location_id" name="location_id" required>
                                <option value="">Select Base Location</option>
                            </select>
                            @error('location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label for="other_location_id" class="form-label">Other Locations </label>
                            <select class="form-control select2 @error('other_location_id') is-invalid @enderror" id="location_id" name="other_location_id[]" multiple>
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('other_location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('other_location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>



                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" maxlength="8" required>
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" maxlength="8" required>
                            @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Guest Allowed -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Guest Allowed <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-4">
                                <div class="form-check me-4">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="personal_guest_flag"
                                        id="personal_guest_flag_no"
                                        value="0"
                                        {{ old('personal_guest_flag', 0) == 0 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="personal_guest_flag_no">
                                        No
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="personal_guest_flag"
                                        id="personal_guest_flag_yes"
                                        value="1"
                                        {{ old('personal_guest_flag') == 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="personal_guest_flag_yes">
                                        Yes
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-4">
                                <div class="form-check me-4">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="status"
                                        id="status_active"
                                        value="1"
                                        {{ old('status', '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="status_active">
                                        Active
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="status"
                                        id="status_inactive"
                                        value="0"
                                        {{ old('status') == '0' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="status_inactive">
                                        Inactive
                                    </label>
                                </div>
                            </div>
                            @error('status')
                            <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>


                        <div id="guest_limits_wrapper" class="row" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label for="max_personal_guest_allowed" class="form-label">Personal Guest Count</label>
                                <input type="number"
                                    class="form-control @error('max_personal_guest_allowed') is-invalid @enderror"
                                    id="max_personal_guest_allowed"
                                    name="max_personal_guest_allowed"
                                    min="0"
                                    value="{{ old('max_personal_guest_allowed', 0) }}">
                                @error('max_personal_guest_allowed')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="max_office_guest_allowed" class="form-label">Office Guest Count</label>
                                <input type="number"
                                    class="form-control @error('max_office_guest_allowed') is-invalid @enderror"
                                    id="max_office_guest_allowed"
                                    name="max_office_guest_allowed"
                                    min="0"
                                    value="{{ old('max_office_guest_allowed', 0) }}">
                                @error('max_office_guest_allowed')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                    </div>
                    <div class="d-flex justify-content-end">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Create User
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
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