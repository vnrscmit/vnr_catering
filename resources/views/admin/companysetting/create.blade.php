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
                        $('#lunch_out_time').val(response.data.lunch_out_time);
                        $('#max_day_show').val(response.data.max_day_show);

                    } else {

                        $('#member_rate').val('');
                        $('#non_member_rate').val('');
                        $('#guest_rate').val('');
                        $('#attendance_out_time').val('');
                        $('#lunch_out_time').val('');
                        $('#max_day_show').val(5);

                    }

                }

            });

        });

    });
</script>
@endpush

@section('title', 'Create Role')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <h5>Create Canteen Setting</h5>
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
                                Member Rate <span class="text-danger">*</span>
                            </label>

                            <input
                                type="number"
                                id="member_rate"
                                name="member_rate"
                                class="form-control"
                                step="0.01"
                                min="0"
                                value="{{ old('member_rate') }}"
                                required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Non Member Rate <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                step="0.01"
                                min="1"
                                id = "non_member_rate"
                                name="non_member_rate"
                                class="form-control"
                                value="{{ old('non_member_rate') }}"
                                required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Guest Rate <span class="text-danger">*</span>
                            </label>

                            <input
                                type="number"
                                id="guest_rate"
                                name="guest_rate"
                                class="form-control"
                                step="0.01"
                                min="1"
                                value="{{ old('guest_rate') }}"
                                required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Attendance Out Time <span class="text-danger">*</span>
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
                            <label class="form-label">
                                Lunch Out Time <span class="text-danger">*</span>
                            </label>

                            <input
                                type="time"
                                id="lunch_out_time"
                                name="lunch_out_time"
                                class="form-control"
                                value="{{ old('lunch_out_time') }}"
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