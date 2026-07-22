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
@endpush

@section('title', 'Create Role')
@section('content')


<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            @include('partials.message-bag')
            <div class="card-header">
                <h5>Create Rate Master</h5>
            </div>

            <div class="card-body">

                <form action="{{ route('rate-masters.store') }}" method="POST">
                    @csrf

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Location <span class="text-danger">*</span></label>

                            <select name="location_id" class="form-control select2" required>
                                <option value="">Select Location</option>

                                @foreach($locations as $location)
                                <option value="{{ $location->id }}" selected>
                                    {{ $location->name }}
                                </option>
                                @endforeach

                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Effective From Month <span class="text-danger">*</span></label>

                            <input type="month"
                                name="effective_month"
                                class="form-control"
                                value="{{ old('effective_month') }}"
                                min="{{ now()->format('Y-m') }}"
                                required>
                        </div>
                    </div>
                    <div class="row">

                        <div class="col-md-3 mb-3">
                            <label>Member Rate <span class="text-danger">*</span></label>

                            <input type="number"
                                step="0.01"
                                min="0"
                                name="member_rate"
                                class="form-control"
                                value="0" required>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label>Non Member Rate <span class="text-danger">*</span></label>
                            <input type="number"
                                step="0.01"
                                min="0"
                                name="non_member_rate"
                                class="form-control"
                                value="0" required>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label>Guest Rate <span class="text-danger">*</span></label>

                            <input type="number"
                                step="0.01"
                                min="0"
                                name="guest_rate"
                                class="form-control"
                                value="0" required>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label>Minimum Days Rate <span class="text-danger">*</span></label>

                            <input type="number"
                                step="0.01"
                                min="0"
                                name="min_day_rate"
                                class="form-control"
                                value="0" required>
                        </div>

                    </div>

                    <div class="d-flex justify-content-end">
                        <div class="mb-3">
                            <button class="btn btn-primary">
                                <i class="fa fa-save"></i> Submit
                            </button>

                            <a href="{{ route('rate-masters.index') }}"
                                class="btn btn-secondary">

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