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

@section('title', 'View Department')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <h5>Department Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="35%">ID:</th>
                                <td>{{ $department->id }}</td>
                            </tr>
                            <tr>
                                <th>Name:</th>
                                <td><strong>{{ $department->name }}</strong></td>
                            </tr>
                            <tr>
                                <th>Short Code:</th>
                                <td><span class="badge bg-info">{{ $department->short_code }}</span></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($department->status === 1)
                                        <span class="badge bg-primary"><i class="fa fa-check"></i> Active</span>
                                    @else
                                        <span class="badge bg-danger"><i class="fa fa-times"></i> Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created:</th>
                                <td>{{ $department->created_at->format('M d, Y h:i A') }}</td>
                            </tr>
                            <tr>
                                <th>Updated:</th>
                                <td>{{ $department->updated_at->format('M d, Y h:i A') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('departments.edit', $department->id) }}" class="btn btn-warning">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                    <a href="{{ route('departments.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
