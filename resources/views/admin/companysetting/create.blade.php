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

        // =============================================
        // Security Deposit Toggle - Event Specific
        // =============================================
        $(document).on('change', '.security-deposit-radio', function() {
            let eventId = $(this).data('event-id');
            let value = $(this).val();

            let amountContainer = $('#security_deposit_amount_container_' + eventId);
            let amountInput = $('#security_deposit_amount_' + eventId);
            let requiredStar = $('#required_star_' + eventId);

            if (value === 'yes') {
                amountContainer.slideDown(300);
                amountInput.prop('required', true);
                amountInput.attr('aria-required', 'true');
                requiredStar.html('*');

                // Add active class
                let parentOption = $(this).closest('.radio-option');
                parentOption.addClass('active');
                parentOption.closest('.radio-group').find('.radio-option').not(parentOption).removeClass('active');
            } else {
                amountContainer.slideUp(300);
                amountInput.prop('required', false);
                amountInput.removeAttr('aria-required');
                amountInput.val('');
                requiredStar.html('');

                // Remove active class
                let parentOption = $(this).closest('.radio-option');
                parentOption.addClass('active');
                parentOption.closest('.radio-group').find('.radio-option').not(parentOption).removeClass('active');
            }
        });

        // =============================================
        // Location change handler
        // =============================================
        $('#location_id').change(function() {
            let locationId = $(this).val();

            if (locationId == '') {
                return;
            }

            $.ajax({
                url: "{{ route('company-parameters.getByLocation','') }}/" + locationId,
                type: "GET",
                dataType: "json",
                success: function(response) {
                    if (response.data) {
                        $('#member_rate').val(response.data.member_rate);
                        $('#non_member_rate').val(response.data.non_member_rate);
                        $('#guest_rate').val(response.data.guest_rate);
                        $('#attendance_out_time').val(response.data.attendance_out_time);
                        $('#max_day_show').val(response.data.max_day_show);
                        $('#min_day').val(response.data.min_day);
                        $('#canteen_start_time').val(response.data.canteen_start_time);
                        $('#canteen_end_time').val(response.data.canteen_end_time);

                        if (response.data.security_deposit_applicable) {
                            $('input[name="security_deposit_applicable"][value="yes"]').prop('checked', true);
                            $('#security_deposit_amount_container').show();
                            $('#security_deposit_amount').val(response.data.security_deposit_amount);
                            $('#security_deposit_amount').prop('required', true);
                        } else {
                            $('input[name="security_deposit_applicable"][value="no"]').prop('checked', true);
                            $('#security_deposit_amount_container').hide();
                            $('#security_deposit_amount').val('');
                            $('#security_deposit_amount').prop('required', false);
                        }
                    } else {
                        $('#member_rate').val('');
                        $('#non_member_rate').val('');
                        $('#guest_rate').val('');
                        $('#attendance_out_time').val('');
                        $('#min_day').val(1);
                        $('#canteen_start_time').val('');
                        $('#canteen_end_time').val('');
                        $('input[name="security_deposit_applicable"][value="no"]').prop('checked', true);
                        $('#security_deposit_amount_container').hide();
                        $('#security_deposit_amount').val('');
                        $('#security_deposit_amount').prop('required', false);
                    }
                }
            });
        });

        // =============================================
        // On page load, check initial state
        // =============================================
        if ($('input[name="security_deposit_applicable"][value="yes"]').prop('checked')) {
            $('#security_deposit_amount_container').show();
            $('#security_deposit_amount').prop('required', true);
        } else {
            $('#security_deposit_amount_container').hide();
            $('#security_deposit_amount').prop('required', false);
        }

        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();

    });

    // =============================================
    // AJAX Form Submission - FIXED
    // =============================================
    $(document).on('submit', 'form[id^="paramForm-"]', function(e) {
        e.preventDefault();

        let form = $(this);
        let formData = form.serialize();
        let eventId = form.find('input[name="event_id"]').val();

        // Get the submit button
        let submitBtn = form.find('button[type="submit"]');
        let originalText = submitBtn.html();

        // Disable button and show loading
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: "{{ route('company-parameters.store') }}",
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                // Reset button state
                submitBtn.prop('disabled', false);
                submitBtn.html(originalText);

                if (response.status === 'success') {

                    let actionText = response.is_update ? 'updated' : 'saved';

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || `Parameters ${actionText} successfully!`,
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: true,
                        confirmButtonColor: '#1A8C39',
                        confirmButtonText: 'OK'
                    });

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Something went wrong!',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr) {
                // Reset button state
                submitBtn.prop('disabled', false);
                submitBtn.html(originalText);

                let errorMessage = 'Something went wrong!';

                if (xhr.status === 422) {
                    // Validation errors
                    let errors = xhr.responseJSON.errors;
                    let errorList = '';

                    $.each(errors, function(key, value) {
                        errorList += '<li>' + value[0] + '</li>';
                    });

                    errorMessage = '<ul style="text-align: left;">' + errorList + '</ul>';

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error!',
                        html: errorMessage,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });

                    // Highlight fields with errors
                    $('.is-invalid').removeClass('is-invalid');
                    $('.invalid-feedback.d-block').remove();

                    $.each(errors, function(key, value) {
                        let field = form.find('[name="' + key + '"]');
                        field.addClass('is-invalid');
                        field.after('<div class="invalid-feedback d-block">' + value[0] + '</div>');
                    });
                } else if (xhr.status === 405) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Method Not Allowed!',
                        text: 'The form action URL is incorrect. Please check the route.',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                } else if (xhr.status === 500) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error!',
                        text: xhr.responseJSON?.message || 'Server error! Please try again later.',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                } else if (xhr.status === 403) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Permission Denied!',
                        text: 'You do not have permission to perform this action.',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || errorMessage,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    });

    // =============================================
    // Reset Form with SweetAlert
    // =============================================
    window.resetFormWithSweetAlert = function() {
        let form = $('form[id^="paramForm-"]').first();

        Swal.fire({
            title: 'Are you sure?',
            text: 'This will reset all form fields. Are you sure you want to continue?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#F2BB1E',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, reset it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Reset the form
                form[0].reset();

                // Reset security deposit state for all events
                $('.security-deposit-radio').each(function() {
                    let eventId = $(this).data('event-id');
                    let amountContainer = $('#security_deposit_amount_container_' + eventId);
                    let amountInput = $('#security_deposit_amount_' + eventId);
                    let requiredStar = $('#required_star_' + eventId);
                    let radioNo = $('#security_deposit_no_' + eventId);
                    let radioYes = $('#security_deposit_yes_' + eventId);

                    radioNo.prop('checked', true);
                    radioYes.prop('checked', false);

                    // Remove active class
                    let yesOption = radioYes.closest('.radio-option');
                    let noOption = radioNo.closest('.radio-option');
                    yesOption.removeClass('active');
                    noOption.addClass('active');

                    amountContainer.hide();
                    amountInput.val('');
                    amountInput.prop('required', false);
                    amountInput.removeAttr('aria-required');
                    requiredStar.html('');

                    // Remove error states
                    amountInput.removeClass('is-invalid');
                    form.find('.invalid-feedback.d-block').remove();
                    form.find('.is-invalid').removeClass('is-invalid');
                });

                Swal.fire({
                    icon: 'success',
                    title: 'Reset Successful!',
                    text: 'All form fields have been reset.',
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            }
        });
    };

    // =============================================
    // Remove error on input change
    // =============================================
    $(document).on('input change', '.is-invalid', function() {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback.d-block').remove();
    });
</script>

<style>
    .radio-group {
        display: flex;
        gap: 20px;
        padding-top: 8px;
    }

    .organization-card {
        display: none;
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .organization-card.show {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .section-header {
        font-size: 16px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }

    .section-header i {
        margin-right: 10px;
        color: #1A8C39;
    }

    .section-description {
        font-size: 13px;
        color: #6c757d;
        margin-bottom: 15px;
        padding: 10px 15px;
        background: #f8f9fa;
    }

    .required-star {
        color: #dc3545;
        margin-left: 3px;
    }

    .text-muted-small {
        font-size: 12px;
        color: #6c757d;
    }

    .divider {
        border-top: 1px solid #e9ecef;
        margin: 20px 0;
    }

    .help-text {
        display: block;
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }

    /* Form Control Enhancements */
    .form-control:focus {
        border-color: #1A8C39;
        box-shadow: 0 0 0 0.2rem rgba(26, 140, 57, 0.25);
    }

    .form-label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .card-header {
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
    }

    .nav-tabs .nav-link {
        color: #495057;
        font-weight: 500;
    }

    .nav-tabs .nav-link.active {
        color: #1A8C39;
        border-bottom: 2px solid #1A8C39;
        font-weight: 600;
    }

    .nav-tabs .nav-link:hover {
        border-color: #e9ecef #e9ecef #dee2e6;
        color: #1A8C39;
    }

    .event-card {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background: #fafbfc;
    }

    .event-card .event-name {
        font-size: 14px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .event-card .event-id {
        font-size: 12px;
        color: #6c757d;
    }
</style>
@endpush

@section('title', 'Create Canteen Parameter')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-utensils me-2"></i> Canteen Parameters - {{ auth()->user()->location->name ?? 'N/A' }}
                    </h5>
                    <a href="{{ route('company-parameters.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            @if($eventData->count())

            <!-- Event Tabs -->
            <ul class="nav nav-tabs mt-3 mx-3" id="eventTabs" role="tablist">
                @foreach($eventData as $index => $locationEvent)
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link {{ $index == 0 ? 'active' : '' }}"
                        id="event-tab-{{ $locationEvent->event_id }}"
                        data-bs-toggle="tab"
                        data-bs-target="#event-content-{{ $locationEvent->event_id }}"
                        type="button"
                        role="tab"
                        aria-selected="{{ $index == 0 ? 'true' : 'false' }}">
                        <i class="fas fa-calendar-alt me-1"></i>
                        {{ $locationEvent->event?->name ?? 'Event #' . $locationEvent->event_id }}
                    </button>
                </li>
                @endforeach
            </ul>

            <!-- Event Tab Content -->
            <div class="tab-content p-3" id="eventTabContent">
                @foreach($eventData as $index => $locationEvent)
                @php
                $existing = $locationEvent->existing_data;

                @endphp
                <div
                    class="tab-pane fade {{ $index == 0 ? 'show active' : '' }}"
                    id="event-content-{{ $locationEvent->event_id }}"
                    role="tabpanel"
                    aria-labelledby="event-tab-{{ $locationEvent->event_id }}">
                    <form action="{{ route('company-parameters.store') }}" method="POST" id="paramForm-{{ $locationEvent->event_id }}">
                        @csrf

                        <input type="hidden" name="location_id" value="{{ $locationEvent->location_id }}">
                        <input type="hidden" name="event_id" value="{{ $locationEvent->event_id }}">

                        <!-- ============================================ -->
                        <!-- SECTION 1: Attendance Configuration          -->
                        <!-- ============================================ -->
                        <div class="organization-card show">
                            <h6 class="section-header">
                                <i class="fas fa-clock"></i> Attendance Configuration
                            </h6>
                            <p class="section-description">
                                <i class="fas fa-info-circle me-1"></i>
                                Configure attendance cut-off times and work schedule rules for this event.
                            </p>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="attendance_out_time_{{ $locationEvent->event_id }}">
                                        Attendance Cut-Off Time
                                        <span class="required-star">*</span>
                                    </label>
                                    <input
                                        type="time"
                                        id="attendance_out_time_{{ $locationEvent->event_id }}"
                                        name="attendance_out_time"
                                        class="form-control"
                                        placeholder="Select time"
                                        value="{{ $existing?->attendance_out_time ? \Carbon\Carbon::parse($existing->attendance_out_time)->format('H:i') : '' }}"
                                        required>
                                    <span class="help-text">Time after which attendance will be marked as late</span>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="max_day_show_{{ $locationEvent->event_id }}">
                                        Max Days to Show
                                        <span class="required-star">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        id="max_day_show_{{ $locationEvent->event_id }}"
                                        name="max_day_show"
                                        class="form-control"
                                        value="{{ $existing->max_day_show ?? 5 }}"
                                        min="1"
                                        max="31"
                                        required>
                                    <span class="help-text">Maximum number of days to display in attendance view</span>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="min_day_{{ $locationEvent->event_id }}">
                                        Minimum Days Present (Monthly)
                                        <span class="required-star">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        id="min_day_{{ $locationEvent->event_id }}"
                                        name="min_day"
                                        class="form-control"
                                        value="{{ $existing->min_day ?? 1 }}"
                                        min="0"
                                        max="31"
                                        required>
                                    <span class="help-text">Minimum days an employee must be present in a month</span>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================ -->
                        <!-- SECTION 2: Canteen Management               -->
                        <!-- ============================================ -->
                        <div class="organization-card show">
                            <h6 class="section-header">
                                <i class="fas fa-utensils"></i> Canteen Management
                            </h6>
                            <p class="section-description">
                                <i class="fas fa-info-circle me-1"></i>
                                Set canteen operating hours and service availability for this event.
                            </p>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="canteen_start_time_{{ $locationEvent->event_id }}">
                                        Canteen Start Time
                                        <span class="required-star">*</span>
                                    </label>
                                    <input
                                        type="time"
                                        id="canteen_start_time_{{ $locationEvent->event_id }}"
                                        name="canteen_start_time"
                                        class="form-control"
                                        placeholder="Select start time"
                                        value="{{ $existing?->canteen_start_time ? \Carbon\Carbon::parse($existing->canteen_start_time)->format('H:i') : '' }}"
                                        required>
                                    <span class="help-text">When canteen service begins</span>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="canteen_end_time_{{ $locationEvent->event_id }}">
                                        Canteen End Time
                                        <span class="required-star">*</span>
                                    </label>
                                    <input
                                        type="time"
                                        id="canteen_end_time_{{ $locationEvent->event_id }}"
                                        name="canteen_end_time"
                                        class="form-control"
                                        placeholder="Select end time"
                                        value="{{ $existing?->canteen_end_time ? \Carbon\Carbon::parse($existing->canteen_end_time)->format('H:i') : '' }}"
                                        required>
                                    <span class="help-text">When canteen service ends</span>
                                </div>
                            </div>
                        </div>

                        <!-- ============================================ -->
                        <!-- SECTION 3: Security Deposit                -->
                        <!-- ============================================ -->
                        @if(strtolower($locationEvent->event?->name) === 'lunch')
                        <div class="organization-card show">
                            <h6 class="section-header">
                                <i class="fas fa-shield-alt"></i> Security Deposit Configuration
                            </h6>
                            <p class="section-description">
                                <i class="fas fa-info-circle me-1"></i>
                                Configure security deposit requirements for employees participating in this event.
                            </p>

                            <div class="row">



                                <div class="col-md-12 mb-3">
                                    <label class="form-label">
                                        Security Deposit Applicable
                                        <span class="required-star">*</span>
                                    </label>

                                    <div class="radio-group-wrapper">
                                        <div class="radio-group">
                                            <!-- Yes Option -->
                                            <div class="form-check radio-option radio-yes">
                                                <input
                                                    type="radio"
                                                    id="security_deposit_yes_{{ $locationEvent->event_id }}"
                                                    name="security_deposit_applicable"
                                                    value="yes"
                                                    class="form-check-input security-deposit-radio"
                                                    data-event-id="{{ $locationEvent->event_id }}"
                                                    {{ ($existing && $existing->security_deposit_applicable == 'yes') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="security_deposit_yes_{{ $locationEvent->event_id }}">
                                                    Applicable
                                                </label>
                                            </div>

                                            <!-- No Option -->
                                            <div class="form-check radio-option radio-no">
                                                <input
                                                    type="radio"
                                                    id="security_deposit_no_{{ $locationEvent->event_id }}"
                                                    name="security_deposit_applicable"
                                                    value="no"
                                                    class="form-check-input security-deposit-radio"
                                                    data-event-id="{{ $locationEvent->event_id }}"
                                                    {{ (!$existing || $existing->security_deposit_applicable == 'no') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="security_deposit_no_{{ $locationEvent->event_id }}">
                                                    Not Applicable
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Security Deposit Amount (conditionally shown) -->
                                <div class="col-md-6 mb-3"
                                    id="security_deposit_amount_container_{{ $locationEvent->event_id }}"
                                    style="display: {{ ($existing && $existing->security_deposit_applicable == 'yes') ? 'block' : 'none' }};">

                                    <label class="form-label" for="security_deposit_amount_{{ $locationEvent->event_id }}">
                                        Security Deposit Amount
                                        <span class="required-star" id="required_star_{{ $locationEvent->event_id }}">
                                            {{ ($existing && $existing->security_deposit_applicable == 'yes') ? '*' : '' }}
                                        </span>
                                    </label>

                                    <div class="input-group">
                                        <span class="input-group-text currency-sign">
                                            <i class="fas fa-rupee-sign"></i>
                                        </span>
                                        <input
                                            type="number"
                                            id="security_deposit_amount_{{ $locationEvent->event_id }}"
                                            name="security_deposit_amount"
                                            class="form-control @error('security_deposit_amount') is-invalid @enderror"
                                            min="0"
                                            step="0.01"
                                            placeholder="Enter deposit amount"
                                            value="{{ $existing->security_deposit_amount ?? '' }}"
                                            {{ ($existing && $existing->security_deposit_applicable == 'yes') ? 'required' : '' }}
                                            aria-required="{{ ($existing && $existing->security_deposit_applicable == 'yes') ? 'true' : 'false' }}">
                                    </div>
                                    <span class="help-text">Amount to be collected as security deposit per employee</span>
                                    @error('security_deposit_amount')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>


                            </div>
                        </div>
                        @else
                        <input type="hidden" name="security_deposit_applicable" value="no">

                        @endif



                        <!-- ============================================ -->
                        <!-- FORM ACTIONS                               -->
                        <!-- ============================================ -->
                        <div class="divider"></div>
                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-end">
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> {{ $existing ? 'Update' : 'Submit' }}
                                </button>

                                <!-- <button type="reset" class="btn" onclick="resetFormWithSweetAlert()" style="background-color: #F2BB1E; border-color: #F2BB1E; color: white;">
                                    <i class="fa fa-undo"></i> Reset
                                </button> -->
                                <a href="{{ route('feedback.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back
                                </a>
                            </div>
                        </div>

                    </form>
                </div>
                @endforeach
            </div>

            @else

            <div class="card-body">
                <div class="alert alert-warning d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                    <div>
                        <strong>No Events Found!</strong>
                        <p class="mb-0">No events are mapped to this location. Please contact your administrator.</p>
                    </div>
                </div>
            </div>

            @endif

        </div>
    </div>
</div>

@endsection