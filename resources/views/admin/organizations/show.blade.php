@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
    <link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endpush

@push('scripts')
    <script src="/admin_resources/vendors/js/vendor.bundle.base.js"></script>
    <script src="/admin_resources/js/off-canvas.js"></script>
    <script src="/admin_resources/js/hoverable-collapse.js"></script>
    <script src="/admin_resources/js/template.js"></script>
    <script src="/admin_resources/js/settings.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endpush

@section('title', 'Organization Details')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Organization Details</h5>
                    <div>
                        <a href="{{ route('admin.organizations.edit', $organization) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('admin.organizations.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <img src="{{ $organization->logo_url }}" alt="{{ $organization->name }}" 
                             style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                        <div class="mt-3">
                            <span class="badge badge-{{ $organization->status ? 'success' : 'danger' }} badge-lg">
                                {{ $organization->status ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Organization Name</label>
                                    <h6>{{ $organization->name }}</h6>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">Short Name</label>
                                    <h6>{{ $organization->short_name }}</h6>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">Address</label>
                                    <p>{{ $organization->address }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">City</label>
                                    <h6>{{ $organization->city }}</h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Town</label>
                                    <h6>{{ $organization->town ?? 'N/A' }}</h6>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">Village</label>
                                    <h6>{{ $organization->village ?? 'N/A' }}</h6>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">Pincode</label>
                                    <h6>{{ $organization->pincode }}</h6>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted small">GSTIN</label>
                                    <h6>{{ $organization->gstin ?? 'N/A' }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Location Settings</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <label class="text-muted small">Max Locations Allowed</label>
                                        <h5 class="text-primary">{{ $organization->max_location_allowed }}</h5>
                                    </div>
                                    <div class="col-6">
                                        <label class="text-muted small">Max Users Per Location</label>
                                        <h5 class="text-primary">{{ $organization->max_user_per_location }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">System Information</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <label class="text-muted small">Created At</label>
                                        <h6>{{ $organization->created_at->format('d M Y, h:i A') }}</h6>
                                    </div>
                                    <div class="col-6">
                                        <label class="text-muted small">Last Updated</label>
                                        <h6>{{ $organization->updated_at->format('d M Y, h:i A') }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection