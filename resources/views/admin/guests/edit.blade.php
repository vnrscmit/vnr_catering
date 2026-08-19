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

@section('title', 'Edit Guest')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">

            <div class="card-header">
                <h5>Edit Guest</h5>
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
                <form action="{{ route('admin.guests.update', $guest->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="calendar_id" value="{{ $guest->calendar_id }}">
                    <div class="row">

                        <!-- Location -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Location <span class="text-danger">*</span>
                            </label>

                            <select class="form-control @error('location_id') is-invalid @enderror" name="location_id" required>
                                <option value="">Select Location</option>

                                @foreach($locations as $location)
                                <option value="{{ $location->id }}"
                                    {{ old('location_id', $guest->location_id) == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                                @endforeach
                            </select>

                            @error('location_id')
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
                                value="{{ old('date', $guest->date ?? date('Y-m-d')) }}"
                                min="{{ date('Y-m-d') }}"
                                required>

                            @error('date')
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
                                        {{ old('guest_type', $guest->guest_type) == 'Office Guest' ? 'checked' : '' }}>

                                    <label class="form-check-label">
                                        Official
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="guest_type"
                                        value="Personal Guest"
                                        {{ old('guest_type', $guest->guest_type) == 'Personal Guest' ? 'checked' : '' }}>

                                    <label class="form-check-label">
                                        Personal
                                    </label>
                                </div>

                            </div>
                        </div>

                        <!-- Department -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Department
                            </label>

                            <select class="form-control @error('department') is-invalid @enderror" name="department_id">
                                <option value="">Select Department</option>

                                @foreach($departments as $department)
                                <option value="{{ $department->id }}"
                                    {{ old('department_id', $guest->department_id) == $department->id ? 'selected' : '' }}>
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
                                Employee
                            </label>

                            <select name="attend_user_id"
                                class="form-control @error('attend_user_id') is-invalid @enderror">
                                <option value="">Select Employee</option>

                                @foreach($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ old('attend_user_id', $guest->attend_user_id) == $user->id ? 'selected' : '' }}>
                                    {{ $user->first_name }}
                                </option>
                                @endforeach
                            </select>

                            @error('attend_user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Guest Count -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Guest Count <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                class="form-control @error('guest_count') is-invalid @enderror"
                                name="guest_count"
                                value="{{ old('guest_count', $guest->guest_count) }}"
                                min="1"
                                placeholder="Enter Guest Count" required>

                            @error('guest_count')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Guest Name -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Guest Name
                            </label>

                            <input type="text"
                                class="form-control @error('guest_name') is-invalid @enderror"
                                name="guest_name"
                                value="{{ old('guest_name', $guest->guest_name) }}"
                                placeholder="e.g. Vendor Team, Client, Family">

                            @error('guest_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label>Remarks</label>
                            <textarea
                                name="guest_remarks" 
                                placeholder="Enter Remarks"
                                class="form-control @error('guest_remarks') is-invalid @enderror">{{ old('guest_remarks', $guest->guest_remarks) }}</textarea>
                            
                            @error('guest_remarks')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Update
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

<script>
    function incrementGuest() {
        let input = document.getElementById('guest_count');
        input.value = parseInt(input.value) + 1;
    }

    function decrementGuest() {
        let input = document.getElementById('guest_count');
        if (parseInt(input.value) > 1) {
            input.value = parseInt(input.value) - 1;
        }
    }
</script>

@endsection