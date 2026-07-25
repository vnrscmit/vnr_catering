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

<script>
    $(document).ready(function() {

        // Security Deposit Toggle
        $('input[name="security_deposit_applicable"]').change(function() {
            if ($(this).val() === 'yes' && $(this).prop('checked')) {
                $('#security_deposit_amount_container').show('slow');
                $('#security_deposit_amount').prop('required', true);
            } else {
                $('#security_deposit_amount_container').hide('slow');
                $('#security_deposit_amount').prop('required', false);
                $('#security_deposit_amount').val('');
            }
        });

        // Location change handler
        $('#location_id').change(function() {

            let locationId = $(this).val();

            if (locationId == '') {
                return;
            }
            console.log(locationId);

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

                        $('#canteen_start_time').val(response.data.canteen_start_time);
                        $('#canteen_end_time').val(response.data.canteen_end_time);

                        // Set security deposit values if they exist
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
                        $('#max_day_show').val(5);

                        $('#canteen_start_time').val('');
                        $('#canteen_end_time').val('');
                        // Reset security deposit
                        $('input[name="security_deposit_applicable"][value="no"]').prop('checked', true);
                        $('#security_deposit_amount_container').hide();
                        $('#security_deposit_amount').val('');
                        $('#security_deposit_amount').prop('required', false);

                    }

                }

            });

        });

        // On page load, check initial state
        if ($('input[name="security_deposit_applicable"][value="yes"]').prop('checked')) {
            $('#security_deposit_amount_container').show();
            $('#security_deposit_amount').prop('required', true);
        } else {
            $('#security_deposit_amount_container').hide();
            $('#security_deposit_amount').prop('required', false);
        }

    });
</script>

<style>
    .radio-group {
        display: flex;
        gap: 20px;
        padding-top: 8px;
    }

    .radio-group .form-check {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .radio-group .form-check input[type="radio"] {
        margin-right: 5px;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .radio-group .form-check label {
        cursor: pointer;
        margin-bottom: 0;
        font-weight: normal;
    }
</style>
@endpush

@section('title', 'Create Role')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <h5>Create Canteen Parameter</h5>
            </div>

            <div class="card-body">
                @include('partials.message-bag')

                <form action="{{ route('company-parameters.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Location <span class="text-danger">*</span>
                            </label>

                            <select name="location_id" id="location_id"
                                class="form-control @error('location_id') is-invalid @enderror"
                                required>

                                <option value="">Select Location</option>

                                @foreach($locations as $location)

                                <option value="{{ $location->id }}"
                                    {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>

                                @endforeach

                            </select>

                            @error('location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Attendance Cut-Off Time <span class="text-danger">*</span>
                            </label>

                            <input
                                type="time"
                                id="attendance_out_time"
                                name="attendance_out_time"
                                class="form-control"
                                value="{{ old('attendance_out_time') }}"
                                required>
                        </div>


                        <div class="col-md-6 mb-3">
                            <label>Canteen Start Time <span class="text-danger">*</span></label>

                            <input type="time" id="canteen_start_time"
                                name="canteen_start_time"
                                class="form-control"
                                value="{{ old('canteen_start_time') }}"
                                required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Canteen End Time <span class="text-danger">*</span></label>

                            <input type="time" id="canteen_end_time"
                                name="canteen_end_time"
                                class="form-control"
                                value="{{ old('canteen_end_time') }}"
                                required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Max Day Show <span class="text-danger">*</span>
                            </label>

                            <input
                                type="number"
                                id="max_day_show"
                                name="max_day_show"
                                class="form-control"
                                value="{{ old('max_day_show',5) }}"
                                min="1"
                                required>
                        </div>

                        <!-- Security Deposit Section -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Security Deposit <span class="text-danger">*</span>
                            </label>

                            <div class="radio-group">
                                <div class="form-check">
                                    <input type="radio"
                                        name="security_deposit_applicable"
                                        id="security_deposit_yes"
                                        value="yes"
                                        {{ old('security_deposit_applicable') == 'yes' ? 'checked' : '' }}>
                                    <label for="security_deposit_yes">Applicable</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio"
                                        name="security_deposit_applicable"
                                        id="security_deposit_no"
                                        value="no"
                                        {{ old('security_deposit_applicable') == 'no' ? 'checked' : '' }}
                                        checked>
                                    <label for="security_deposit_no">Not Applicable</label>
                                </div>
                            </div>

                            @error('security_deposit_applicable')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Security Deposit Amount (shown only when Yes is selected) -->
                        <div class="col-md-6 mb-3 security-deposit-container"
                            id="security_deposit_amount_container"
                            style="display: {{ old('security_deposit_applicable') == 'yes' ? 'block' : 'none' }};">
                            <label class="form-label">
                                Security Deposit Amount <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number"
                                    id="security_deposit_amount"
                                    name="security_deposit_amount"
                                    class="form-control"
                                    placeholder="Enter security deposit amount"
                                    value="{{ old('security_deposit_amount') }}"
                                    min="0"
                                    step="0.01"
                                    {{ old('security_deposit_applicable') == 'yes' ? 'required' : '' }}>
                            </div>
                            @error('security_deposit_amount')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="d-flex justify-content-end">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Submit
                            </button>
                            <a href="{{ route('company-parameters.index') }}" class="btn btn-secondary">
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