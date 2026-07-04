@extends('layouts.admin')

@section('title', 'Admin - Manage Guests')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        @include('partials.message-bag')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Guests Management</span>
                <div class="d-flex align-items-center gap-2">
                    <form method="GET" action="{{ route('admin.guests.index') }}" class="d-flex align-items-center gap-2">
                        <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Filter</button>
                    </form>
                    <a href="{{ route('admin.guests.create') }}" class="btn btn-primary btn-sm">Add Guest</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <h6 class="mb-1">Total Guests</h6>
                            <h3 class="mb-0">{{ $summary['total_guest'] }}</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <h6 class="mb-1">Personal Guests</h6>
                            <h3 class="mb-0">{{ $summary['personal_guest_count'] }}</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <h6 class="mb-1">Office Guests</h6>
                            <h3 class="mb-0">{{ $summary['office_guest_count'] }}</h3>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
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
                                    <td>{{ optional($guest->calendar)->date }}</td>
                                    <td>{{ $guest->status ? 'Active' : 'Inactive' }}</td>
                                    <td>
                                        <a href="{{ route('admin.guests.edit', $guest->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form action="{{ route('admin.guests.destroy', $guest->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this guest?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
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
