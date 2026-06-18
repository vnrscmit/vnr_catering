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

@section('title', 'Role Master')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        @include('partials.message-bag')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Role Master ({{ $roles->count() }})</span>
                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> Add Role
                </a>
            </div>
            <div class="card-body">
                @if($roles->isEmpty())
                    <div class="alert alert-warning" role="alert">
                        <i class="fa fa-info-circle"></i> No role records found.
                    </div>
                @else
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Short Code</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $role->name }}</strong></td>
                                    <td>{{ $role->short_code }}</td>
                                    <td>
                                        @if($role->status === 1)
                                            <span class="badge bg-success"><i class="fa fa-check"></i> Active</span>
                                        @else
                                            <span class="badge bg-danger"><i class="fa fa-times"></i> Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="" class="btn btn-info btn-sm" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure?')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center">
                        {{ $roles->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
