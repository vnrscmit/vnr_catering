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

@section('title', 'Add Guest')
@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        @include('partials.message-bag')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">Guests Management</span>

                <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-md-4 mb-4">
                        <div class="card upcoming-card h-100">
                            <div class="card-body">
                                <p class="summary-label">Personal Guests</p>
                                <h2 class="summary-count">
                                    {{ $summary['personal_guest_count'] }}
                                </h2>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card upcoming-card h-100">
                            <div class="card-body">
                                <p class="summary-label">Office Guests</p>
                                <h2 class="summary-count">
                                    {{ $summary['office_guest_count'] }}
                                </h2>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mb-4">
                        <div class="card upcoming-card h-100">
                            <div class="card-body">
                                <p class="summary-label">Total Guests</p>
                                <h2 class="summary-count">
                                    {{ $summary['total_guest'] }}
                                </h2>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Count</th>
                                <th>Location</th>
                                <th>Department</th>
                                <th>Attended By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($guests as $guest)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $guest->guest_name }}</td>
                                <td>{{ $guest->guest_type }}</td>
                                <td>{{ $guest->guest_count }}</td>
                                <td>{{ $guest->location->name ?? '-' }}</td>
                                <td>{{ $guest->department->name ?? '-' }}</td>
                                <td>{{ $guest->attendUser->first_name ?? '-' }}</td>
                                <td>
                                    {{ optional($guest->calendar)->date ? \Carbon\Carbon::parse($guest->calendar->date)->format('d-m-Y') : '-' }}
                                </td>
                                <td>{{ $guest->status ? 'Active' : 'Inactive' }}</td>
                                <td>
                                    <a href="{{ route('admin.guests.edit', $guest->id) }}"
                                        class="btn btn-warning btn-sm"
                                        title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>

                                    <form action="{{ route('admin.guests.destroy', $guest->id) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Delete this guest?');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center">No guests found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('partials.admin.footer')
</div>
@endsection