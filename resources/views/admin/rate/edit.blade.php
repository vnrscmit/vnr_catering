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
@section('title', 'Edit - Rate Master')
@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
                    @include('partials.message-bag')
            <div class="card-header">
                <h5 class="card-title mb-0">Edit Rate Master - {{ auth()->user()->location->name ?? 'N/A' }} </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('rate-masters.update', $rateMaster->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- Location -->
                         <input type="hidden" value="{{ $locationId }}" name="location_id">
                        <div class="col-md-6 mb-3">
                            <label>Effective From Month <span class="text-danger">*</span></label>
                            <input type="month"
                                name="effective_month"
                                class="form-control @error('effective_month') is-invalid @enderror"
                                min="{{ date('Y-m', strtotime('-1 month')) }}"
                                max="{{ date('Y-m', strtotime('+12 months')) }}"
                                value="{{ old('effective_month', $effectiveMonth) }}"
                                required>
                            @error('effective_month')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <small class="text-muted">
                                <i class="fa fa-info-circle"></i>
                                Allowed: Current month, Future months, and Last month only
                            </small>
                        </div>

                        <!-- Event -->
                        <div class="col-md-6 mb-3">
                            <label>Event <span class="text-danger">*</span></label>
                            <select name="event_id" class="form-control @error('event_id') is-invalid @enderror" required>
                                <option value="">Select Event</option>
                                @foreach($eventList as $eventId => $eventName)
                                <option value="{{ $eventId }}"
                                    {{ old('event_id', $rateMaster->event_id) == $eventId ? 'selected' : '' }}>
                                    {{ $eventName }}
                                </option>
                                @endforeach
                            </select>
                            @error('event_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>


                    <div class="row">
                        <!-- Member Rate -->
                        <div class="col-md-3 mb-3">
                            <label>Member Rate <span class="text-danger">*</span></label>
                            <input type="number"
                                name="member_rate"
                                class="form-control @error('member_rate') is-invalid @enderror"
                                value="{{ old('member_rate', $rateMaster->member_rate) }}"
                                step="0.01"
                                min="0"
                                required>
                            @error('member_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Guest Rate -->
                        <div class="col-md-3 mb-3">
                            <label>Guest Rate <span class="text-danger">*</span></label>
                            <input type="number"
                                name="guest_rate"
                                class="form-control @error('guest_rate') is-invalid @enderror"
                                value="{{ old('guest_rate', $rateMaster->guest_rate) }}"
                                step="0.01"
                                min="0"
                                required>
                            @error('guest_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Non Member Rate -->
                        <div class="col-md-3 mb-3">
                            <label>Non Member Rate <span class="text-danger">*</span></label>
                            <input type="number"
                                name="non_member_rate"
                                class="form-control @error('non_member_rate') is-invalid @enderror"
                                value="{{ old('non_member_rate', $rateMaster->non_member_rate) }}"
                                step="0.01"
                                min="0"
                                required>
                            @error('non_member_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label>Min Day Rate <span class="text-danger">*</span></label>
                            <input type="number"
                                name="min_day_rate"
                                class="form-control @error('min_day_rate') is-invalid @enderror"
                                value="{{ old('min_day_rate', $rateMaster->min_day_rate) }}"
                                step="0.01"
                                min="0"
                                required>
                            @error('min_day_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <div class="mb-0">
                            <button class="btn btn-primary">
                                <i class="fa fa-save"></i> Update
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