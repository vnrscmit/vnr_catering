@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
<link rel="stylesheet" href="/admin_resources/vendors/select2/select2.min.css">
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
<script src="/admin_resources/vendors/select2/select2.min.js"></script>
<script>
    $(function () {
        $('.select2').select2({
            placeholder: 'Select Locations',
            allowClear: true
        });
    });
</script>
@endpush

@section('content')
<div class="main-panel">
    <div class="content-wrapper">

        {{-- Department Details --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Linked Details</h5>
            </div>

              <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="20%">Department</th>
                        <td>{{ $data->name }}</td>
                    </tr>

                    <tr>
                        <th>Status</th>
                        <td>
                            @if($data->status)
                                <span class="badge bg-primary">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                    </tr>

                
                </table>
            </div>

            <div class="card-body">

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Department</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alreadylinkedData as $key=>$row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->department_name }}</td>
                            <td>{{ $row->location_name }}</td>
                        </tr>

                        @empty

                        <tr>
                            <td colspan="3" class="text-center">
                                No Location Linked
                            </td>
                        </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>
        </div>


        {{-- Link Locations --}}
        <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Add New Location</h5>
            </div>
            <div class="card-body">

                <form action="{{ route('locations.link.store') }}" method="POST">
                    @csrf
                    <input type="hidden"
                        name="department_id"
                        value="{{ $data->id }}">

                    <div class="mb-3">
                        <label class="form-label"> <strong>Select Locations</strong>
                        </label>
                        <select
                            name="location_id[]"
                            class="form-control select2"
                            multiple>
                          <option value="">Select Locations</option>
                            @foreach($locations as $location)

                            <option value="{{ $location->id }}">
                                {{ $location->name }}
                            </option>

                            @endforeach

                        </select>
                    </div>

                    <button class="btn btn-primary">
                        <i class="fa fa-link"></i>
                        Link Locations
                    </button>

                </form>




            </div>
        </div>

    </div>
</div>

@endsection