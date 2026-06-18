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

@section('title', 'Location Master')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        @include('partials.message-bag')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Location Master ({{ $locations->count() }})</span>
                <a href="{{ route('locations.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> Add Location
                </a>
            </div>
            <div class="card-body">
                @if($locations->isEmpty())
                    <div class="alert alert-warning" role="alert">
                        <i class="fa fa-info-circle"></i> No location records found.
                    </div>
                @else
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Short Code</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($locations as $location)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $location->name }}</strong></td>
                                    <td>{{ $location->short_code }}</td>
                                    <td>
                                        @if($location->status == 1)
                                            <span class="badge bg-success"><i class="fa fa-check"></i> Active</span>
                                        @else
                                            <span class="badge bg-danger"><i class="fa fa-times"></i> Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $location->created_at->format('d-m-Y') }}</td>
                                    <td>
                                        <a href="{{ route('locations.show', $location->id) }}" class="btn btn-info btn-sm" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="{{ route('locations.edit', $location->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <form action="{{ route('locations.destroy', $location->id) }}" method="POST" style="display:inline;">
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
                        {{ $locations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
