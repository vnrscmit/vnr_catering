@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .user-detail-card {
        display: none;
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .user-detail-card.show {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    .user-detail-item {
        padding: 5px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .user-detail-item:last-child {
        border-bottom: none;
    }

    .user-detail-label {
        font-weight: 600;
        color: #495057;
        width: 100px;
        display: inline-block;
    }

    .user-detail-value {
        color: #212529;
    }

    .settlement-card {
        display: none;
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .settlement-card.show {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    .user-detail-card.show {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    /* Error state styling */
    .is-invalid {
        border-color: #dc3545 !important;
        background-color: #fff8f8 !important;
    }

    .is-invalid:focus {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        10%,
        30%,
        50%,
        70%,
        90% {
            transform: translateX(-5px);
        }

        20%,
        40%,
        60%,
        80% {
            transform: translateX(5px);
        }
    }

    .shake-error {
        animation: shake 0.5s ease-in-out;
    }
</style>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('title','Generate Bill')

@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            @include('partials.message-bag')
            <div class="card-header">
                <h5 class="card-title mb-0">Create Individual Settlement</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('bill-generate.individual.store') }}" method="POST" id="rateMasterForm">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Department <span class="text-danger">*</span></label>
                            <select name="department_id" id="departmentSelect" class="form-control select2" required>
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->department_id }}">
                                    {{ $department->department_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Select User <span class="text-danger">*</span></label>
                            <select name="user_id" id="userSelect" class="form-control select2" required disabled>
                                <option value="">Select User</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Diet Chargable Date<span class="text-danger">*</span></label>
                            <input type="date"
                                id="chargeDate"
                                name="charge_date"
                                class="form-control"
                                min="{{ now()->startOfMonth()->format('Y-m-d') }}"
                                max="{{ now()->format('Y-m-d') }}"
                                required
                                disabled>
                            <small class="text-muted">Select date from which diet charges should start</small>
                        </div>

                    </div>

                    <!-- User Details Panel -->
                    <div class="user-detail-card" id="userDetailCard">
                        <h6 class="card-title mb-2"><i class="fas fa-user"></i> User Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="user-detail-item">
                                    <span class="user-detail-label">Name:</span>
                                    <span class="user-detail-value" id="userDetailName">-</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="user-detail-item">
                                    <span class="user-detail-label">Role:</span>
                                    <span class="user-detail-value" id="userDetailRole">-</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="user-detail-item">
                                    <span class="user-detail-label">Email:</span>
                                    <span class="user-detail-value" id="userDetailEmail">-</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="user-detail-item">
                                    <span class="user-detail-label">Phone:</span>
                                    <span class="user-detail-value" id="userDetailPhone">-</span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ===== NEW SETTLEMENT FIELDS ===== -->
                    <div class="settlement-card" id="settlementCard">
                        <h6 class="card-title">
                            <i class="fas fa-calculator"></i> Settlement Details
                        </h6>

                        <!-- ROW 1 : 3 COLUMNS -->
                        <div class="row">

                            <!-- Month -->
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="settlementMonth">
                                        Month <span class="text-danger">*</span>
                                    </label>
                                    <input type="month"
                                        id="settlementMonth"
                                        name="settlement_month"
                                        class="form-control"
                                        value="{{ date('Y-m') }}"
                                        required
                                        readonly>
                                    <small class="text-muted">Current month</small>
                                </div>
                            </div>

                            <!-- Settlement Date -->
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="settlementDate">
                                        Settlement Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                        id="settlementDate"
                                        name="settlement_date"
                                        class="form-control"
                                        value="{{ date('Y-m-d') }}"
                                        required
                                        readonly>
                                    <small class="text-muted">Current date</small>
                                </div>
                            </div>

                            <!-- Current Outstanding -->
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="currentOutstanding">
                                        Current Outstanding <span class="text-danger">*</span>
                                    </label>

                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number"
                                            step="0.01"
                                            min="0"
                                            id="currentOutstanding"
                                            name="current_outstanding"
                                            class="form-control"
                                            value="0"
                                            required readonly>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- ROW 2 : 3 COLUMNS -->
                        <div class="row">

                            <!-- Total Diets -->
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="totalDiets">
                                        Total Diets <span class="text-danger">*</span>
                                    </label>

                                    <input type="number"
                                        min="0"
                                        id="totalDiets"
                                        name="total_diets"
                                        class="form-control"
                                        value="0"
                                        required
                                        readonly>

                                    <small class="text-muted">
                                        Total diets in current month
                                    </small>
                                </div>
                            </div>

                            <!-- Rate -->
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="rate">
                                        Rate <span class="text-danger">*</span>
                                    </label>

                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number"
                                            step="0.01"
                                            min="0"
                                            name="rate"
                                            class="form-control" id="previousMonthRate"
                                            value="0"
                                            required>
                                    </div>
                                    <small class="text-muted">
                                        Reference Rate from previous month / Editable
                                    </small>
                                </div>
                            </div>

                            <!-- Total Net Chargable -->
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="totalNetChargable">
                                        Total Diet Charges Due
                                    </label>

                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="text"
                                            id="totalNetChargable"
                                            name="total_net_chargable"
                                            class="form-control"
                                            value="0"
                                            readonly>
                                    </div>

                                    <small class="text-muted">
                                        Auto-calculated (Diets × Rate)
                                    </small>
                                </div>
                            </div>

                        </div>

                        <!-- ROW 3 : 3 COLUMNS -->
                        <div class="row">

                            <!-- Security Deposit -->
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="securityDeposit">
                                        Security Deposit <span class="text-danger">*</span>
                                    </label>

                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number"
                                            step="0.01"
                                            min="0"
                                            id="securityDeposit"
                                            name="security_deposit"
                                            class="form-control"
                                            value="0"
                                            required
                                            readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Net Settlement Charges -->
                            <div class="col-md-4 mb-3">
                                <div class="form-group">
                                    <label for="netSettlementCharges">
                                        Net Settlement Charges <span class="text-danger">*</span>
                                    </label>
                                    
                                    <span id="settlementType" class="ml-6 d-none"></span>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="text"
                                            id="netSettlementCharges"
                                            class="form-control"
                                            value="0"
                                            readonly>
                                    </div>

                                    <small class="text-muted">
                                        (Current Outstanding + Total Diet Charges Due) - Security Deposit
                                    </small>

                                    <!-- Actual value submitted -->
                                    <input type="hidden"
                                        id="netSettlementChargesInput"
                                        name="net_settlement_charges"
                                        value="0">
                                </div>
                            </div>

                            <!-- Empty third column -->
                            <div class="col-md-4 mb-3">
                            </div>

                        </div>

                        <div class="divider"></div>

                        <!-- Additional Notes -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="notes">
                                        Remarks
                                    </label>
                                    <textarea id="notes"
                                        name="remarks"
                                        class="form-control"
                                        rows="2"
                                        placeholder="Add any additional notes or remarks"></textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="d-flex justify-content-end mt-2">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary" id="submitBtn" name="action" value="2">
                                <i class="fa fa-file"></i> Preview & Submit
                            </button>

                            <a href="{{ route('rate-masters.index') }}" class="btn btn-secondary ms-2">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>

                </form>

            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {

        // =============================================
        // SWEETALERT FUNCTIONS
        // =============================================

        function showSuccessAlert(title, message) {
            Swal.fire({
                icon: 'success',
                title: title || 'Success!',
                text: message || 'Operation completed successfully.',
                timer: 3000,
                timerProgressBar: true,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        }

        function showErrorAlert(title, message) {
            Swal.fire({
                icon: 'error',
                title: title || 'Error!',
                text: message || 'Something went wrong.',
                confirmButtonColor: '#d33',
                confirmButtonText: 'OK'
            });
        }

        function showWarningAlert(title, message) {
            Swal.fire({
                icon: 'warning',
                title: title || 'Warning!',
                text: message || 'Please check your input.',
                confirmButtonColor: '#f39c12',
                confirmButtonText: 'OK'
            });
        }

        function showInfoAlert(title, message) {
            Swal.fire({
                icon: 'info',
                title: title || 'Information',
                text: message || '',
                confirmButtonColor: '#17a2b8',
                confirmButtonText: 'OK'
            });
        }

        // =============================================
        // SESSION MESSAGES FROM BACKEND
        // =============================================

        @if(session('success'))
        showSuccessAlert('Success!', '{{ session('
            success ') }}');
        @endif

        @if(session('error'))
        showErrorAlert('Error!', '{{ session('
            error ') }}');
        @endif

        @if(session('warning'))
        showWarningAlert('Warning!', '{{ session('
            warning ') }}');
        @endif

        @if(session('info'))
        showInfoAlert('Information', '{{ session('
            info ') }}');
        @endif

        // =============================================
        // DEPARTMENT CHANGE EVENT
        // =============================================

        $('#departmentSelect').on('change', function() {
            var departmentId = $(this).val();
            var userSelect = $('#userSelect');
            var userDetailCard = $('#userDetailCard');
            var settlementCard = $('#settlementCard');

            // Clear current options and hide details
            userSelect.empty().append('<option value="">Select User</option>');
            userSelect.prop('disabled', true);
            userDetailCard.removeClass('show');
            settlementCard.removeClass('show');
            $('#chargeDate').prop('disabled', true).val('');

            if (departmentId) {
                userSelect.append('<option value="" disabled selected>Loading users...</option>');
                userSelect.trigger('change');

                $.ajax({
                    url: '/get-users-by-department/' + departmentId,
                    type: 'GET',
                    dataType: 'json',
                    timeout: 10000,
                    beforeSend: function() {
                        userSelect.prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            userSelect.empty().append('<option value="">Select User</option>');

                            if (response.users && response.users.length > 0) {
                                $.each(response.users, function(index, user) {
                                    userSelect.append(
                                        $('<option>', {
                                            value: user.id,
                                            text: user.name + ' (' + user.role + ')'
                                        })
                                    );
                                });
                                userSelect.prop('disabled', false);
                                showSuccessAlert('Users Loaded', 'Users loaded successfully!');
                            } else {
                                userSelect.append('<option value="">No users found</option>');
                                userSelect.prop('disabled', true);
                                showWarningAlert('No Users', 'No users found in this department.');
                            }
                        } else {
                            userSelect.empty().append('<option value="">Error loading users</option>');
                            userSelect.prop('disabled', true);
                            showErrorAlert('Error', response.message || 'Failed to load users');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        userSelect.empty().append('<option value="">Error loading users</option>');
                        userSelect.prop('disabled', true);

                        var errorMsg = 'Error loading users. ';
                        if (status === 'timeout') {
                            errorMsg += 'Request timed out. Please try again.';
                        } else if (xhr.status === 403) {
                            errorMsg += 'You do not have permission.';
                        } else if (xhr.status === 404) {
                            errorMsg += 'Department not found.';
                        } else {
                            errorMsg += 'Please try again.';
                        }
                        showErrorAlert('Error', errorMsg);
                    },
                    complete: function() {
                        userSelect.trigger('change');
                    }
                });
            }
        });

        // =============================================
        // USER SELECT CHANGE EVENT
        // =============================================

        $('#userSelect').on('change', function() {
            var userId = $(this).val();
            var chargeDate = $('#chargeDate');

            if (userId) {
                chargeDate.prop('disabled', false);
                // Set default date to today
                var today = new Date().toISOString().split('T')[0];
                chargeDate.attr('max', today);
            } else {
                chargeDate.prop('disabled', true);
                chargeDate.val('');
                $('#userDetailCard').removeClass('show');
                $('#settlementCard').removeClass('show');
            }
        });

        // =============================================
        // CHARGE DATE CHANGE EVENT - MAIN LOGIC
        // =============================================

        $('#chargeDate').on('change', function() {
            var chargeDate = $(this).val();
            var userId = $('#userSelect').val();
            var userDetailCard = $('#userDetailCard');
            var settlementCard = $('#settlementCard');

            // Clear previous error states
            $(this).removeClass('is-invalid shake-error');

            if (!userId) {
                showWarningAlert('Warning', 'Please select a user first.');
                return;
            }

            if (!chargeDate) {
                showWarningAlert('Warning', 'Please select a charge date.');
                return;
            }

            // Show loading state
            Swal.fire({
                title: 'Loading...',
                text: 'Fetching user details...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Fetch user details
            $.ajax({
                url: '/get-user-details/' + userId + '/' + chargeDate,
                type: 'GET',
                dataType: 'json',
                timeout: 5000,
                success: function(response) {
                    Swal.close();

                    if (response.success && response.user) {
                        var user = response.user;

                        // Update user details
                        $('#userDetailName').text(user.name || '');
                        $('#userDetailEmail').text(user.email || '');
                        $('#userDetailPhone').text(user.phone || '');
                        $('#userDetailRole').text(user.role || '');

                        // Update settlement fields
                        $('#securityDeposit').val(
                            user.security_deposit ? parseFloat(user.security_deposit).toFixed(0) : '0'
                        );
                        $('#totalDiets').val(
                            user.total_diets ? parseFloat(user.total_diets).toFixed(0) : '0'
                        );
                        $('#previousMonthRate').val(
                            user.previous_month_rate ? parseFloat(user.previous_month_rate).toFixed(0) : '0'
                        );
                        $('#currentOutstanding').val(
                            user.current_outstanding ? parseFloat(user.current_outstanding).toFixed(0) : '0'
                        );

                        // Calculate charges
                        calculateCharges();

                        // Show details
                        userDetailCard.addClass('show');
                        settlementCard.addClass('show');

                        showSuccessAlert('Success!', 'User details loaded successfully!');
                    } else {
                        // HIDE DETAILS ON ERROR
                        userDetailCard.removeClass('show');
                        settlementCard.removeClass('show');

                        // Get error message from response
                        var errorMessage = response.message || 'Failed to load user details';

                        // Highlight charge date field
                        $('#chargeDate').addClass('is-invalid shake-error');

                        // Show error with SweetAlert
                        showErrorAlert('Validation Error!', errorMessage);

                        // Remove error class after 5 seconds
                        setTimeout(function() {
                            $('#chargeDate').removeClass('is-invalid shake-error');
                        }, 5000);
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();

                    // HIDE DETAILS ON ERROR
                    userDetailCard.removeClass('show');
                    settlementCard.removeClass('show');

                    var errorMsg = '';

                    // Check if error response has message
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.status === 404) {
                        errorMsg = 'User not found or invalid date. Please check the chargeable date.';
                    } else if (xhr.status === 422) {
                        errorMsg = 'Validation error. Please check your input.';
                    } else if (xhr.status === 403) {
                        errorMsg = 'You do not have permission to access this user.';
                    } else if (status === 'timeout') {
                        errorMsg = 'Request timed out. Please try again.';
                    } else {
                        errorMsg = 'Failed to load user details. Please try again.';
                    }

                    // Highlight charge date field
                    $('#chargeDate').addClass('is-invalid shake-error');

                    // Show error with SweetAlert
                    showErrorAlert('Error!', errorMsg);

                    // Remove error class after 5 seconds
                    setTimeout(function() {
                        $('#chargeDate').removeClass('is-invalid shake-error');
                    }, 5000);
                }
            });
        });

        // =============================================
        // CALCULATE CHARGES
        // =============================================

        $('#previousMonthRate, #totalDiets, #securityDeposit').on('input change', function() {
            calculateCharges();
        });

        function calculateCharges() {
            var rate = parseFloat($('#previousMonthRate').val()) || 0;
            var totalDiets = parseFloat($('#totalDiets').val()) || 0;
            var securityDeposit = parseFloat($('#securityDeposit').val()) || 0;
            var currentOutstanding = parseFloat($('#currentOutstanding').val()) || 0;

            var totalNetChargable = rate * totalDiets;
            $('#totalNetChargable').val(totalNetChargable.toFixed(0));

            var netSettlementCharges = (totalNetChargable - securityDeposit) + currentOutstanding;
            $('#netSettlementCharges').val(netSettlementCharges.toFixed(0));
            $('#netSettlementChargesInput').val(netSettlementCharges.toFixed(0));

            $('#settlementType')
                .removeClass('text-danger text-success')
                .addClass('d-none');

            if (netSettlementCharges < 0) {
                $('#settlementType')
                    .text('Payable')
                    .removeClass('d-none')
                    .addClass('text-danger');

            } else if (netSettlementCharges > 0) {
                $('#settlementType')
                    .text('Receivable')
                    .removeClass('d-none')
                    .addClass('text-success');
            }
        }

        // Initial calculation
        setTimeout(function() {
            calculateCharges();
        }, 100);

        // =============================================
        // FORM SUBMISSION WITH CONFIRMATION
        // =============================================

        $('#rateMasterForm').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var departmentId = $('#departmentSelect').val();
            var userId = $('#userSelect').val();
            var chargeDate = $('#chargeDate').val();

            // Validate required fields
            if (!departmentId) {
                showWarningAlert('Validation Error', 'Please select a department.');
                return false;
            }

            if (!userId) {
                showWarningAlert('Validation Error', 'Please select a user.');
                return false;
            }

            if (!chargeDate) {
                showWarningAlert('Validation Error', 'Please select a charge date.');
                return false;
            }

            // Check if user details are loaded
            if (!$('#userDetailCard').hasClass('show')) {
                showWarningAlert('Warning', 'Please load user details first by selecting a charge date.');
                return false;
            }

            // Show confirmation dialog
            Swal.fire({
                title: 'Confirm Submission',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Submit!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we generate the bill.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Submit the form
                    form.off('submit').submit();
                }
            });
        });

        // =============================================
        // TRIGGER CHARGE DATE CHANGE ON PAGE LOAD
        // =============================================

        // If user is pre-selected and charge date has value
        if ($('#userSelect').val() && $('#chargeDate').val()) {
            $('#chargeDate').trigger('change');
        }

    });
</script>
@endpush
@endsection