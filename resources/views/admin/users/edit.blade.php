@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function resetFormWithSweetAlert() {
        Swal.fire({
            title: 'Are you sure?',
            text: "All form fields will be reset to original values!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, reset it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Reload page to reset to original values
                location.reload();
                Swal.fire(
                    'Reset!',
                    'Form has been reset to original values.',
                    'success'
                );
            }
        });
    }

    $(function() {
        // Initialize select2
        $('.select2').select2({
            placeholder: 'Select Locations',
            allowClear: true
        });

        // Function to handle role-based field visibility
        function toggleFieldsByRole() {
            var roleId = $('#role_id').val();
            var roleName = $('#role_id option:selected').text();

            // Check if selected role is "Canteen Administrator"
            if (roleName.trim() == 'Canteen Administrator') {
                // Hide the fields
                $('#mobile_field').hide();
                $('#email_field').hide();
                $('#user_code_field').hide();
                $('#username_field').hide();

                // Remove required attributes
                $('#mobile').removeAttr('required');
                $('#email').removeAttr('required');
                $('#user_code').removeAttr('required');
                $('#username').removeAttr('required');
            } else {
                // Show the fields
                $('#mobile_field').show();
                $('#email_field').show();
                $('#user_code_field').show();
                $('#username_field').show();

                // Add back required attributes
                $('#mobile').attr('required', true);
                $('#email').attr('required', true);
                $('#user_code').attr('required', true);
                $('#username').attr('required', true);
            }
        }

        // Trigger on role change
        $('#role_id').on('change', toggleFieldsByRole);

        // Run on page load
        toggleFieldsByRole();

        // Set initial values for location dropdowns
        var initialDepartmentId = $('#department_id').val();
        var initialLocationId = '{{ $userToEdit->location_id }}';
        
        if (initialDepartmentId) {
            loadLocations(initialDepartmentId, initialLocationId);
        }

        // Department change event
        $('#department_id').on('change', function() {
            let departmentId = $(this).val();
            loadLocations(departmentId, null);
        });

        // Location change event for security amount
        $('#location_id').on('change', function() {
            let locationId = $(this).val();
            if (locationId != '') {
                $.ajax({
                    url: "{{ url('admin/get-security-amount') }}/" + locationId,
                    type: "GET",
                    success: function(response) {
                        $('#security_amount').val(response.security_amount);
                    }
                });
            } else {
                $('#security_amount').val('');
            }
        });

        // Function to load locations
        function loadLocations(departmentId, selectedLocationId) {
            $('#location_id').html('<option value="">Select Location</option>');
            
            if (departmentId) {
                $.ajax({
                    url: "{{ url('admin/get-location') }}/" + departmentId,
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        $.each(response, function(index, location) {
                            var selected = (selectedLocationId && selectedLocationId == location.location_id) ? 'selected' : '';
                            $('#location_id').append(
                                '<option value="' + location.location_id + '" ' + selected + '>' +
                                location.location_name +
                                '</option>'
                            );
                        });
                    }
                });
            }
        }

        // Toggle guest fields
        function toggleGuestFields() {
            const guestAllowed = $('input[name="personal_guest_flag"]:checked').val();
            if (guestAllowed == '1') {
                $('#guest_limits_wrapper').slideDown();
            } else {
                $('#guest_limits_wrapper').slideUp();
                $('#max_personal_guest_allowed').val(0);
                $('#max_office_guest_allowed').val(0);
            }
        }

        // Trigger on both radio buttons
        $('input[name="personal_guest_flag"]').on('change', toggleGuestFields);

        // Run on page load
        toggleGuestFields();

        // Password validation (optional)
        $('#password, #password_confirmation').on('keyup', function() {
            var password = $('#password').val();
            var confirmPassword = $('#password_confirmation').val();
            
            if (password.length > 0 || confirmPassword.length > 0) {
                if (password !== confirmPassword) {
                    $('#password_confirmation').addClass('is-invalid');
                } else {
                    $('#password_confirmation').removeClass('is-invalid');
                }
            }
        });
       

        // Trigger username generation on first name change
        $('#first_name').on('input', generateUsername);


        // Add username hint
        $('#username_field').append(`
            <small class="text-muted">Auto-generates from full name</small>
        `);
     
    });

    // Global functions for button clicks
    function generateUserCode() {
        const firstName = $('#first_name').val().trim();
        if (firstName) {
            const code = firstName.substring(0, 3).toUpperCase();
            $('#user_code').val(code);
        }
    }
</script>
@endpush

@section('title', 'Edit User')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Edit User</h5>
            </div>
            <div class="card-body">

                <!-- ERROR MESSAGES -->
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

                <form action="{{ route('admin.users.update', $userToEdit->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <!-- Role Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-control @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $userToEdit->role_id) == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('role_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- President Flag -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Canteen President <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex align-items-center gap-4">
                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="president_flag"
                                        id="president_flag_yes"
                                        value="1"
                                        {{ old('president_flag', $userToEdit->president_flag) == 1 ? 'checked' : '' }}
                                        {{ $presidentLock ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="president_flag_yes">
                                        Yes
                                    </label>
                                </div>

                                <div class="form-check me-4">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="president_flag"
                                        id="president_flag_no"
                                        value="0"
                                        {{ old('president_flag', $userToEdit->president_flag) == 0 ? 'checked' : '' }}
                                        {{ $presidentLock ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="president_flag_no">
                                        No
                                    </label>
                                </div>
                            </div>

                            @if($presidentLock)
                            <input type="hidden" name="president_flag" value="{{ old('president_flag', $userToEdit->president_flag) }}">
                            @endif

                            @if($presidentLock && $userToEdit->president_flag == 0)
                            <small class="text-warning">⚠️ President already exists for this location</small>
                            @endif

                            @error('president_flag')
                            <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- User Code - Wrapped in div for hiding -->
                        <div class="col-md-6 mb-3" id="user_code_field">
                            <label for="user_code" class="form-label">
                                Employee ID <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control @error('user_code') is-invalid @enderror"
                                id="user_code"
                                name="user_code"
                                value="{{ old('user_code', $userToEdit->user_code) }}"
                                maxlength="5"
                                minlength="1"
                                pattern="[A-Za-z0-9]{1,5}"
                                oninput="this.value=this.value.replace(/[^A-Za-z0-9]/g,'').toUpperCase()"
                                required>
                            @error('user_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- First Name -->
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name', $userToEdit->first_name) }}" required>
                            @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Username - NEW FIELD wrapped in div for hiding -->
                        <div class="col-md-6 mb-3" id="username_field">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control @error('username') is-invalid @enderror"
                                id="username"
                                name="username"
                                value="{{ old('username', $userToEdit->username) }}"
                                placeholder="e.g., admin123"
                                minlength="3"
                                maxlength="50"
                                required>
                            <small class="text-muted">Auto-generates from full name</small>
                            @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Mobile - Wrapped in div for hiding -->
                        <div class="col-md-6 mb-3" id="mobile_field">
                            <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('mobile') is-invalid @enderror" id="mobile" name="mobile" value="{{ old('mobile', $userToEdit->mobile) }}" placeholder="e.g., 9876543210" required>
                            @error('mobile')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email - Wrapped in div for hiding -->
                        <div class="col-md-6 mb-3" id="email_field">
                            <label for="email" class="form-label">e-Mail ID <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $userToEdit->email) }}" required>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Department Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-control @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department_id', $userToEdit->department_id) == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Designation -->
                        <div class="col-md-6 mb-3">
                            <label for="designation" class="form-label">Designation</label>
                            <input type="text" class="form-control @error('designation') is-invalid @enderror" id="designation" name="designation" value="{{ old('designation', $userToEdit->designation) }}" placeholder="e.g., Senior Developer">
                            @error('designation')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location Dropdown (Primary) -->
                        <div class="col-md-6 mb-3">
                            <label for="location_id" class="form-label">Canteen Base Location <span class="text-danger">*</span></label>
                            <select class="form-control @error('location_id') is-invalid @enderror" id="location_id" name="location_id" required>
                                <option value="">Select Base Location</option>
                                @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id', $userToEdit->location_id) == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Additional Locations Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label for="other_location_id" class="form-label">Additional Canteen Locations</label>
                            <select class="form-control select2 @error('other_location_id') is-invalid @enderror" 
                                    id="other_location_id" 
                                    name="other_location_id[]" 
                                    multiple>
                                @foreach($allLocations as $location)
                                    <option value="{{ $location->id }}" 
                                        {{ in_array($location->id, old('other_location_id', $otherLocationIds ?? [])) ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('other_location_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>  

                        <!-- Security Amount -->
                        <div class="col-md-6 mb-3">
                            <label for="security_amount" class="form-label">
                                Security Amount
                            </label>
                            <input type="number"
                                class="form-control @error('security_amount') is-invalid @enderror"
                                id="security_amount"
                                name="security_amount"
                                value="{{ old('security_amount', $userToEdit->security_amount ?? 0) }}"
                                step="0.01">
                            @error('security_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Payment Method and Deposit Date -->
                        <div class="col-md-6 mb-3">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="payment_method" class="form-label">
                                        Payment Method
                                    </label>
                                    <select class="form-control @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method">
                                        <option value="">Select Payment Method</option>
                                        <option value="Cash" {{ old('payment_method', $userToEdit->payment_method) == 'Cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="UPI" {{ old('payment_method', $userToEdit->payment_method) == 'UPI' ? 'selected' : '' }}>UPI</option>
                                        <option value="Both" {{ old('payment_method', $userToEdit->payment_method) == 'Both' ? 'selected' : '' }}>Both</option>
                                    </select>
                                    @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="deposit_date" class="form-label">
                                        Security Deposit Collection Date
                                    </label>
                                    <input type="date"
                                        class="form-control @error('deposit_date') is-invalid @enderror"
                                        id="deposit_date"
                                        name="deposit_date"
                                        min="{{ \Carbon\Carbon::today()->subDays(15)->format('Y-m-d') }}"
                                        max="{{ date('Y-m-d') }}"
                                        value="{{ old('deposit_date', $userToEdit->deposit_date ? \Carbon\Carbon::parse($userToEdit->deposit_date)->format('Y-m-d') : '') }}">
                                    @error('deposit_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                Password 
                                <small class="text-muted">(Leave blank to keep current password)</small>
                            </label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" maxlength="8" placeholder="Enter new password">
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" maxlength="8" placeholder="Confirm new password">
                            @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Guest Allowed -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Guest Allowed <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-4">
                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="personal_guest_flag"
                                        id="personal_guest_flag_yes"
                                        value="1"
                                        {{ old('personal_guest_flag', $userToEdit->personal_guest_flag) == 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="personal_guest_flag_yes">
                                        Yes
                                    </label>
                                </div>

                                <div class="form-check me-4">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="personal_guest_flag"
                                        id="personal_guest_flag_no"
                                        value="0"
                                        {{ old('personal_guest_flag', $userToEdit->personal_guest_flag) == 0 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="personal_guest_flag_no">
                                        No
                                    </label>
                                </div>
                            </div>
                            @error('personal_guest_flag')
                            <div class="text-danger small">{{ $message }}</div>
                            @enderror
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
                                        {{ old('status', $userToEdit->status) == '1' ? 'checked' : '' }}>
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
                                        {{ old('status', $userToEdit->status) == '0' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="status_inactive">
                                        Inactive
                                    </label>
                                </div>
                            </div>
                            @error('status')
                            <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Guest Limits -->
                        <div id="guest_limits_wrapper" class="row" style="{{ old('personal_guest_flag', $userToEdit->personal_guest_flag) == 1 ? 'display: flex;' : 'display: none;' }}">
                            <div class="col-md-6 mb-3">
                                <label for="max_personal_guest_allowed" class="form-label">Personal Guest Count in one day</label>
                                <input type="number"
                                    class="form-control @error('max_personal_guest_allowed') is-invalid @enderror"
                                    id="max_personal_guest_allowed"
                                    name="max_personal_guest_allowed"
                                    min="0"
                                    value="{{ old('max_personal_guest_allowed', $userToEdit->max_personal_guest_allowed ?? 0) }}">
                                @error('max_personal_guest_allowed')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="max_office_guest_allowed" class="form-label">Office Guest Count in one day</label>
                                <input type="number"
                                    class="form-control @error('max_office_guest_allowed') is-invalid @enderror"
                                    id="max_office_guest_allowed"
                                    name="max_office_guest_allowed"
                                    min="0"
                                    value="{{ old('max_office_guest_allowed', $userToEdit->max_office_guest_allowed ?? 0) }}">
                                @error('max_office_guest_allowed')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>

                    <div class="d-flex justify-content-end">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Update
                            </button>
                            <button type="button" class="btn" onclick="resetFormWithSweetAlert()" style="background-color: #117a8b; border-color: #10707f; color: white;">
                                <i class="fa fa-undo"></i> Reset
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