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

@section('title', 'Opening Amount')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        @include('partials.message-bag')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Add Opening Amount - {{ auth()->user()->location->name ?? 'N/A' }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('opening-amount.store') }}" method="POST">
                    @csrf

                    <!-- Row 1: Transaction Date & Select User -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date" class="form-label">Transaction Date <span class="text-danger">*</span></label>
                            <input type="date"
                                class="form-control @error('date') is-invalid @enderror"
                                id="date"
                                name="date"
                                value="{{ old('date', date('Y-m-d')) }}"
                                required>
                            @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="user_id" class="form-label">Select User <span class="text-danger">*</span></label>
                            <select class="form-control @error('user_id') is-invalid @enderror"
                                id="user_id"
                                name="user_id"
                                required>
                                <option value="">Select User</option>
                                @foreach($users ?? [] as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->first_name ?? $user->name ?? '' }}
                                </option>
                                @endforeach
                            </select>
                            @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Row 2: Opening Amount & Mode of Collection -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Opening Balance (+Due / -Advance)<span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number"
                                    class="form-control @error('amount') is-invalid @enderror"
                                    id="amount"
                                    name="amount"
                                    value="{{ old('amount') }}"
                                    step="0.01"
                                    placeholder="Enter opening amount"
                                    required>
                            </div>
                            @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" name="status" value="1">

                    <!-- Buttons -->
                    <div class="row">
                        <div class="col-12 d-flex justify-content-end">
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Submit
                                </button>
                                <a href="{{ route('opening-amount.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .input-group-text {
        background-color: #f8f9fa;
    }

    .form-check {
        margin-bottom: 5px;
    }

    input[readonly] {
        background-color: #f8f9fa;
        cursor: not-allowed;
    }

    textarea {
        resize: vertical;
    }
</style>

@endsection