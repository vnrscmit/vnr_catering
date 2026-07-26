@extends('layouts.admin')

@push('styles')
<!-- base:css -->
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
<!-- plugin js for this page -->
<script src="/admin_resources/vendors/progressbar.js/progressbar.min.js"></script>
<script src="/admin_resources/vendors/chart.js/Chart.min.js"></script>
<!-- Custom js for this page-->
<script src="/admin_resources/js/dashboard.js"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


@endpush


@section('title', 'Admin - Settings - Categories')

@section('content')

<div class="main-panel">
    <div class="content-wrapper">

        @include('partials.message-bag')


        <div class="card card-info">
            <div class="card-header">
                <h5 class="card-title mb-0"> <i class="fa fa-user"></i> My Profile</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-center align-items-center">
                    <!-- Profile Photo Preview -->
                    <div class="mb-3 text-center">
                        <img src="{{ $user->profile_picture ? asset('storage/profile-picture/' . $user->profile_picture) : asset('assets/images/user-icon.png') }}"
                            alt="Profile Preview"
                            class="img-thumbnail"
                            style="width: 150px; height: 150px;">
                    </div>
                </div>
                <hr />
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th width="40%">Name</th>
                            <td>{{ $user->first_name }}</td>
                        </tr>

                            <tr>
                            <th>Employee Code</th>
                            <td>{{ $user->user_code  ?? ' ' }}</td>
                        </tr>

                        <tr>
                            <th>Mobile</th>
                            <td>{{ $user->mobile }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $user->email ?? ' ' }}</td>
                        </tr>

                    

                        <tr>
                            <th>Designation</th>
                            <td>{{ $user->designation ?? 'N/A' }}</td>
                        </tr>

                        <tr>
                            <th>Role</th>
                            <td>{{ $user->role ?? 'N/A' }}</td>
                        </tr>

                        <tr>
                            <th>Department</th>
                            <td>{{ optional($user->department)->name ?? 'N/A' }}</td>
                        </tr>

                        <tr>
                            <th>Location</th>
                            <td>{{ optional($user->location)->name ?? 'N/A' }}</td>
                        </tr>

                        <tr>
                            <th>Guest Allowed</th>
                            <td>{{ $user->personal_guest_flag ? 'Yes' : 'No' }}</td>
                        </tr>

                        <tr>
                            <th>Max Personal Guest</th>
                            <td>{{ $user->max_personal_guest_allowed }}</td>
                        </tr>

                        <tr>
                            <th>Max Office Guest</th>
                            <td>{{ $user->max_office_guest_allowed }}</td>
                        </tr>


                        <tr>
                            <th>Status</th>
                            <td>
                                @if($user->status)
                                <span class="badge bg-primary">Active</span>
                                @else
                                <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>

            </div>
            <div class="card-footer">
                <button type="button" onclick="window.location='{{ route('admin.myprofile.edit') }}'" class="btn btn-primary">Edit My Profile</button>
                <button type="button" onclick="window.location='{{ route('admin.dashboard') }}'" class="btn btn-secondary float-right">Back</button>
            </div>
        </div>






    </div>
    <!-- content-wrapper ends -->
    @include('partials.admin.footer')
</div>
<!-- main-panel ends -->
@endsection