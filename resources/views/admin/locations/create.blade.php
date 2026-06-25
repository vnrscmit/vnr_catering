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

@section('title', 'Create Location')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <h5>Create New Location</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('locations.store') }}" method="POST">
                    @csrf
                    <div class="row">
                    <div class="mb-3 col-6">
                        <label for="name" class="form-label">Location Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 col-6">
                        <label for="short_code" class="form-label">Short Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('short_code') is-invalid @enderror" id="short_code" name="short_code" value="{{ old('short_code') }}" placeholder="e.g., LOC" required>
                        @error('short_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

<div class="mb-3 col-6">
    <label class="form-label">
        Status <span class="text-danger">*</span>
    </label>

    <div class="d-flex align-items-center gap-4">
        <div class="form-check me-4">
            <input class="form-check-input"
                type="radio"
                name="status"
                id="status_active"
                value="1"
                checked>
            <label class="form-check-label" for="status_active">
                Active
            </label>
        </div>

        <div class="form-check">
            <input class="form-check-input"
                type="radio"
                name="status"
                id="status_inactive"
                value="0">
            <label class="form-check-label" for="status_inactive">
                Inactive
            </label>
        </div>
    </div>
    </div>
</div>
<div class="d-flex justify-content-end">
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Create Location
                        </button>
                        <a href="{{ route('locations.index') }}" class="btn btn-secondary">
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
