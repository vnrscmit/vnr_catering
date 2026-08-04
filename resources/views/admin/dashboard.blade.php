@extends('layouts.admin')

@push('styles')
<!-- base:css -->
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
<link rel="stylesheet" href="/admin_resources/css/small-box.css">
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
  $(document).ready(function() {

    $('#member_filter, #attendance_filter').on('change', function() {

      var member = $('#member_filter').val();
      var attendance = $('#attendance_filter').val();

      $('table tbody tr').each(function() {

        var rowMember = $(this).data('member');
        var rowAttendance = $(this).data('attendance');

        var memberMatch = (member === '' || rowMember === member);
        var attendanceMatch = (attendance === '' || rowAttendance === attendance);

        if (memberMatch && attendanceMatch) {
          $(this).show();
        } else {
          $(this).hide();
        }

      });

    });

  });
  let attendanceStatus = 0;
  let currentButton = null;

  // Override Modal Open
  $(document).on('click', '.override-btn', function() {

    // Same row ka attendance button
    currentButton = $(this).closest('tr').find('.attendance-btn');

    attendanceStatus = currentButton.data('status');

    $('#override_user_id').val($(this).data('user-id'));
    $('#userName').text($(this).data('user-name'));
    $('#departmentName').text($(this).data('department'));
    $('#overrideRemark').val('');

    $('.status-btn').removeClass('active');

    if (attendanceStatus == 0) {

      $('.status-btn[data-value="0"]').addClass('active');

      $('#currentStatus')
        .text('Present')
        .removeClass('badge-absent')
        .addClass('badge-present');

    } else {

      $('.status-btn[data-value="1"]').addClass('active');

      $('#currentStatus')
        .text('Absent')
        .removeClass('badge-present')
        .addClass('badge-absent');
    }

  });

  // Status Change
  $(document).on('click', '.status-btn', function() {

    $('.status-btn').removeClass('active');
    $(this).addClass('active');

    attendanceStatus = $(this).data('value');

  });

  // Attendance Toggle
  $(document).on('click', '.attendance-btn', function() {

    let button = $(this);

    $.ajax({
      url: "{{ route('attendance.toggle') }}",
      type: "POST",
      data: {
        _token: "{{ csrf_token() }}",
        user_id: button.data('user-id'),
        absent_flag: button.data('status')
      },

      beforeSend: function() {
        button.prop('disabled', true).text('Please Wait...');
      },

      success: function(response) {

        if (!response.success) {

          Swal.fire({
            icon: 'warning',
            title: 'Warning',
            text: response.message
          });

          return;
        }

        updateAttendanceUI(button, response.absent_flag);

      },

      error: function() {

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Something went wrong.'
        });

      },

      complete: function() {
        button.prop('disabled', false);
      }

    });

  });

  // Override Save
  $(document).on('click', '#saveOverride', function() {

    let btn = $(this);

    $.ajax({

      url: "{{ route('attendance.override') }}",
      type: "POST",

      data: {
        _token: "{{ csrf_token() }}",
        user_id: $('#override_user_id').val(),
        absent_flag: attendanceStatus,
        remarks: $('#overrideRemark').val()
      },

      beforeSend: function() {

        btn.prop('disabled', true)
          .html('<i class="fa fa-spinner fa-spin"></i> Saving...');

      },

      success: function(response) {

        if (!response.success) {

          Swal.fire({
            icon: 'warning',
            title: 'Warning',
            text: response.message
          });

          return;
        }

        $('#overrideModal').modal('hide');

        Swal.fire({
          icon: 'success',
          title: 'Success',
          text: response.message,
          timer: 1500,
          showConfirmButton: false
        });

        updateAttendanceUI(currentButton, response.absent_flag);

      },

      error: function(xhr) {

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: xhr.responseJSON?.message ?? 'Something went wrong.'
        });

      },

      complete: function() {

        btn.prop('disabled', false)
          .html('Confirm');

      }

    });

  });

  // Common UI Update Function
  function updateAttendanceUI(button, absentFlag) {
    button.data('status', absentFlag);

    if (absentFlag == 1) {

      button
        .removeClass('btn-outline-danger')
        .addClass('btn-outline-primary')
        .text('Mark Present');

      button.closest('tr').find('.attendance-badge')
        .removeClass('bg-primary')
        .addClass('bg-danger')
        .text('Absent');

    } else {

      button
        .removeClass('btn-outline-primary')
        .addClass('btn-outline-danger')
        .text('Mark Absent');

      button.closest('tr').find('.attendance-badge')
        .removeClass('bg-danger')
        .addClass('bg-primary')
        .text('Present');
    }

    // Override button ka status bhi update
    button.closest('tr').find('.override-btn').data('status', absentFlag);
  }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
  .summary-card {
    border-radius: 20px;
    padding: 15px 10px;
    text-align: center;
    box-shadow: 0 3px 8px rgba(0, 0, 0, .10);
    transition: .3s;
    border: 1px solid rgba(0, 0, 0, .05);
    min-height: 160px;
  }

  .summary-card:hover {
    transform: translateY(-3px);
  }

  .summary-icon {
    font-size: 28px;
    /* 42px -> 28px */
    margin-bottom: 8px;
  }

  .summary-count {
    font-size: 40px;
    /* 52px -> 40px */
    font-weight: 700;
    line-height: 1;
    margin: 6px 0;
  }

  .summary-title {
    font-size: 18px;
    /* 20px -> 18px */
    font-weight: 500;
    margin: 0;
  }

  /* Present */
  .present-card {
    background: #EEF7E7;
  }

  .present-card .summary-icon,
  .present-card .summary-count,
  .present-card .summary-title {
    color: #3B7A1E;
  }

  /* Absent */
  .absent-card {
    background: #FFF2C9;
  }

  .absent-card .summary-icon,
  .absent-card .summary-count,
  .absent-card .summary-title {
    color: #C06A00;
  }

  /* Office Guest */
  .office-card {
    background: #EAF3FF;
  }

  .office-card .summary-icon,
  .office-card .summary-count,
  .office-card .summary-title {
    color: #2563EB;
  }

  /* Personal Guest */
  .personal-card {
    background: #F4EDFF;
  }

  .personal-card .summary-icon,
  .personal-card .summary-count,
  .personal-card .summary-title {
    color: #7C3AED;
  }

  .user-card {
    background: #f6f7fb;
    padding: 15px;
    border-radius: 18px;
  }

  .avatar {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    background: #59c248;
    color: #fff;
    font-size: 22px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .status-buttons {
    display: flex;
    gap: 15px;
  }

  .status-btn {
    flex: 1;
    border: 2px solid #7DA548;
    background: #fff;
    color: #7DA548;
    border-radius: 14px;
    padding: 14px;
    font-size: 18px;
    font-weight: 600;
    transition: .3s;
  }

  .status-btn.active {
    background: #6B962C;
    color: #fff;
  }

  #currentStatus {
    font-size: 15px;
  }

  .badge-present {
    background: #E7F8EC;
    color: #2E7D32;
  }

  .badge-absent {
    background: #FFE8E8;
    color: #D32F2F;
  }

  .attendance-circle {
    --progress: 75;

    width: 180px;
    height: 180px;
    margin: auto;
    border-radius: 50%;

    background: conic-gradient(#5B43D6 calc(var(--progress) * 1%),
        #E7E8F5 0);

    display: flex;
    align-items: center;
    justify-content: center;

    transition: .5s;
  }

  .attendance-circle-inner {

    width: 135px;
    height: 135px;

    background: #fff;
    border-radius: 50%;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    box-shadow: 0 5px 20px rgba(0, 0, 0, .08);
  }

  .attendance-circle-inner h2 {

    margin: 0;
    font-size: 38px;
    font-weight: 700;
    color: #5B43D6;
  }

  .attendance-circle-inner span {

    color: #777;
    font-size: 14px;
  }
</style>
@endpush

@section('title', 'Admin - Dashboard')
@section('content')

<div class="main-panel">
  <div class="content-wrapper">
    @include('partials.message-bag')
    @include('partials.order-stats')

    <!-- ============ ADD THIS ============ -->
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <div>
            <h4 class="card-title mb-0 fw-bold">
              <i class="fa fa-calendar-check-o me-2 text-primary"></i>
              Dashboard
            </h4>
          </div>
          <div class="d-flex align-items-center gap-3 mt-2 mt-sm-0">
            <div class="text-end">
              <div class="fw-bold" style="font-size: 1.1rem; color: #2c3e50;">
                {{ \Carbon\Carbon::now()->format('l, F j, Y') }}
              </div>
            </div>
            <div class="vr" style="height: 30px;"></div>
          </div>
        </div>
        <hr class="my-2">
      </div>
    </div>

    <!-- ============ END ADD ============ -->

    @if($allLocked)
    <p style="color:red">Your start Date is not set please contact to your canteen incharge</p>
    @else
    <div class="row">
      @if(count($allLocations) > 1)
      <div class="col-12">
        <div class="card-body">
          <div class="row">
            <div class="">
              @php
              $selectedLocation = request()->route('id') ?? $allLocations->first()['location_id'];
              @endphp

              <div class="mb-3">
                @foreach($allLocations as $location)
                <a href="{{ route('admin.dashboard', ['id' => $location['location_id']]) }}"
                  class="btn me-2 mb-2 {{ $selectedLocation == $location['location_id'] ? 'btn-primary active' : 'btn-outline-primary' }}">
                  {{ $location['location_name'] }}
                </a>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </div>

      @endif

      <!-- First Condition  -->
      @if($overrideLock)
      <div class="col-12 mb-4">
        <div class="card-body">
          <div class="row">

            <!-- Present -->
            <div class="col-md-3 col-6 mb-4">
              <div class="summary-card present-card">
                <div class="summary-icon">
                  <i class="fa fa-user-check"></i>
                </div>

                <h2 class="summary-count">{{ $presentCount ?? 0 }}</h2>

                <p class="summary-title">Present</p>
              </div>
            </div>

            <!-- Absent -->
            <div class="col-md-3 col-6 mb-4">
              <div class="summary-card absent-card">
                <div class="summary-icon">
                  <i class="fa fa-user-times"></i>
                </div>

                <h2 class="summary-count">{{ $absentCount ?? 0 }}</h2>

                <p class="summary-title">Absent</p>
              </div>
            </div>

            <!-- Office Guests -->
            <div class="col-md-3 col-6 mb-4">
              <div class="summary-card office-card">
                <div class="summary-icon">
                  <i class="fa fa-users"></i>
                </div>

                <h2 class="summary-count">{{ $totalGuest ?? 0 }}</h2>

                <p class="summary-title">Guests</p>
              </div>
            </div>

            <!-- Personal Guests -->
            <div class="col-md-3 col-6 mb-4">
              <div class="summary-card personal-card">
                <div class="summary-icon">
                  <i class="fa fa-utensils"></i>
                </div>

                <h2 class="summary-count">{{ $totalMeal ?? 0 }}</h2>

                <p class="summary-title">Required Meal</p>
              </div>
            </div>

          </div>
        </div>
      </div>
      @endif

      <!-- Second Condition  -->
      @if(
      $UserData->role !== 'Canteen Incharge' &&
      $UserData->role !== 'Super Admin' &&
      $UserData->role !== 'Canteen Administrator'
      )
      <div class="col-12 col-lg-6 mb-4">
        <div class="card lunch-card">
          <div class="card-body">
            <div class="card-header-simple">
              <h3 class="upcoming-title">Today's Lunch</h3>
              <div class="card-date">
                {{ date('l, M d') }}
              </div>
            </div>
            @if($isLocked)
            @if($todaysAttendance && $todaysAttendance->absent_flag == 0)

            {{-- LOCKED + PRESENT --}}
            <div class="d-flex align-items-center mt-4">
              <div class="status-icon">
                <i class="fa fa-lock"></i>
              </div>

              <div class="ms-4">
                <div class="present-title">
                  You're Present
                </div>

                <div class="present-subtitle">
                  Your lunch has been successfully reserved.
                </div>
              </div>
            </div>

            <div class="menu-box mt-3">
              <h6>Today's Menu</h6>

              <div class="row">
                @foreach($todayMenu as $menu)
                <div class="col-md-6 mb-2">
                  <div class="menu-item">
                    <i class="fa fa-utensils me-2"></i>
                    {{ $menu['name'] }}
                    @if($menu['special_flag'] == 1)
                   <i class="fa fa-star text-warning" title="Special"></i>
                    @endif
                  </div>
                </div>
                @endforeach
              </div>
            </div>

            @else
            {{-- LOCKED + ABSENT --}}
            <div class="d-flex align-items-center mt-4">
              <div class="status-icon bg-danger-subtle text-danger">
                <i class="fa fa-lock"></i>
              </div>
              <div class="ms-4">
                <div class="present-title text-danger">
                  Marked Absent
                </div>
                <div class="present-subtitle">
                  Lunch reservation is closed.
                </div>
              </div>
            </div>
            @endif

            @else
            @if($todaysAttendance && $todaysAttendance->absent_flag == 0 )
            <div class="d-flex align-items-center mt-4">
              <div class="status-icon">
                <i class="fa fa-check"></i>
              </div>
              <div class="ms-4">
                <div class="present-title">
                  You're Present
                </div>

                <div class="present-subtitle">
                  Your lunch has been successfully reserved.
                </div>
              </div>

            </div>

            <div class="timer-box">

              <span>
                <i class="fa fa-clock me-2"></i>
                Cancellation closes in
              </span>

              <strong id="countdown">
                {{ $remainingSeconds ? gmdate('H:i:s', $remainingSeconds) : '00:00:00' }}
              </strong>

            </div>
            <div class="menu-box">
              <h6>Today's Menu</h6>
              <div class="row">
                @foreach($todayMenu as $menu)
                <div class="col-md-6 mb-2">
                  <div class="menu-item">
                    <i class="fa fa-utensils me-2"></i>
                    {{ $menu['name'] }}
                    @if($menu['special_flag'] == 1)
                       <i class="fa fa-star text-warning" title="Special"></i>
                    @endif
                  </div>
                </div>
                @endforeach
              </div>
            </div>
            <div class="row mt-4">
              <div class="col-6">
                <button class="btn btn-menu w-100"
                  data-bs-toggle="modal"
                  data-bs-target="#todayMenuModal">
                  <i class="fa fa-book-open me-2"></i>
                  View Menu
                </button>
              </div>

              <div class="col-6">
                <a href="{{ route('mark-attendance', $dayStatus->id) }}"> <button class="btn btn-absent w-100">
                    <i class="fa fa-times-circle me-2"></i>
                    Mark Absent
                  </button></a>
              </div>

            </div>
            @else
            <div class="d-flex align-items-center mt-4">
              <div class="d-flex align-items-center mt-4">
                <div class="status-icon bg-danger-subtle text-danger me-4">
                  <i class="fa fa-times"></i>
                </div>

                <div>
                  <h3 class="marked-absent mb-0">
                    Marked Absent
                  </h3>
                </div>
              </div>

            </div>
            <div class="undo-timer-box mt-4">
              <i class="fa fa-clock me-2"></i>
              You can undo until
              <strong>{{ \Carbon\Carbon::parse($companyParameter->attendance_out_time)->format('h:i A') }}</strong>

              (<span id="countdown">
                {{ $remainingSeconds ? gmdate('H:i:s', $remainingSeconds) : '00:00:00' }}
              </span> left)
            </div>

            <div class="mt-4">
              <a href="{{ route('mark-attendance', $dayStatus->id) }}"
                class="btn btn-undo w-100">
                <i class="fa fa-rotate-left"></i>
                Undo - Mark Present
              </a>
            </div>
            @endif
            @endif
          </div>
        </div>
      </div>
      <!-- ......................................Upcoming days...................................... -->
      <div class="col-12 col-lg-6 mb-4">
        <div class="card upcoming-card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h3 class="upcoming-title">Upcoming Days</h3>
              <div class="card-date">
                Tap to mark absent
              </div>

            </div>
            <div class="row g-4">
              @foreach($upComingDays as $day)
              <div class="col-lg-3 col-md-6">
                <div class="day-card {{ $day->absent_flag ? 'absent' : 'present' }}">

                  <div class="day-name">
                    {{ \Carbon\Carbon::parse($day->date)->format('D') }}
                  </div>

                  <div class="day-date">
                    {{ \Carbon\Carbon::parse($day->date)->format('d') }}
                  </div>
                  <a href="{{ route('mark-attendance', $day->id) }}">
                    @if($day->absent_flag)
                    <div class="status-circle absent-icon">
                      <i class="fa fa-times"></i>
                    </div>
                    <div class="status-text text-muted">
                      Absent
                    </div>
                  </a>
                  @else
                  <div class="status-circle success">
                    <i class="fa fa-check"></i>
                  </div>
                  <div class="status-text">
                    Present
                  </div>
                  @endif
                  </a>
                </div>
              </div>
              @endforeach

            </div>

          </div>
        </div>
      </div>

      @if($summaryCurrentMonth)
      <!-- ......................................Attendance Summary...................................... -->
      <div class="col-12 col-lg-6 mb-4">
        <div class="card guest-card">
          <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">

              <!-- Left Section -->
              <div class="d-flex align-items-center">
                <div class="status-icon">
                  <i class="fa fa-clipboard-list"></i>
                </div>
                <div class="ms-3">
                  <h3 class="guest-title mb-1">Attendance Summary</h3>
                  <p>{{ now()->format('F Y') }}</p>
                </div>
              </div>
            </div>
            <div class="row">
              <!-- Present Card -->
              <div class="col-6">
                <div class="guest-box" style="background:#EEF8E8;">
                  <div class="guest-label" style="color:#3E7B27;font-size:11px;font-weight:600;letter-spacing:.5px;">
                    <i class="fa fa-check-circle me-1"></i> PRESENT
                  </div>

                  <div class="guest-count" style="color:#3E7B27;">
                    {{ $summaryCurrentMonth->presentDays ?? 0}}
                  </div>

                  <div class="guest-limit">
                    days
                  </div>

                  <div class="progress guest-progress">
                    <div class="progress-bar"
                      style="width:{{ $summaryCurrentMonth->presentPercentage ?? 0 }}%;background:#5A8F29;">
                    </div>
                  </div>

                  <div class="guest-percentage mt-1"
                    style="color:#3E7B27;font-size:14px;font-weight:600;">
                    {{ $summaryCurrentMonth->presentPercentage ?? 0 }}%
                  </div>
                </div>
              </div>

              <!-- Absent Card -->
              <div class="col-6">
                <div class="guest-box" style="background:#FFF1F1;">
                  <div class="guest-label" style="color:#E53935;font-size:11px;font-weight:600;letter-spacing:.5px;">
                    <i class="fa fa-times-circle me-1"></i> ABSENT
                  </div>

                  <div class="guest-count" style="color:#E53935;">
                    {{ $summaryCurrentMonth->absent_days ?? 0}}
                  </div>

                  <div class="guest-limit">
                    days
                  </div>

                  <div class="progress guest-progress">
                    <div class="progress-bar"
                      style="width:{{ ($summaryCurrentMonth->absentPercentage ?? 0) }}%;background:#E53935;">
                    </div>
                  </div>

                  <div class="guest-percentage mt-1"
                    style="color:#E53935;font-size:14px;font-weight:600;">
                    {{($summaryCurrentMonth->absentPercentage ?? 0) }}%
                  </div>
                </div>
              </div>

              <!-- Attendance Rate -->
              <div class="col-12 mt-3">
                <div class="guest-box text-center" style="background:#F8F9FF;padding:25px;border-radius:16px;">

                  <div class="guest-label mb-3"
                    style="color:#5B43D6;font-size:13px;font-weight:600;letter-spacing:.5px;">
                    <i class="fa fa-chart-line me-1"></i>
                    ATTENDANCE RATE
                  </div>

                  <div class="attendance-circle"
                    style="--progress: {{ $summaryCurrentMonth->presentPercentage ?? 0 }};">
                    <div class="attendance-circle-inner">
                      <h2>{{ $summaryCurrentMonth->presentPercentage }}%</h2>
                      <span>Overall</span>
                    </div>

                  </div>

                </div>
              </div>

            </div>


          </div>
        </div>
      </div>
      @endif
      @endif

      <!-- Third Condition  -->

      @if($guestAllowed)
      <!-- ================= Guest Card ================= -->
      <div class="col-12 col-lg-6 mb-4">
        <div class="card guest-card">
          <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">

              <!-- Left Section -->
              <div class="d-flex align-items-center">
                <div class="status-icon">
                  <i class="fa fa-users"></i>
                </div>

                <div class="ms-3">
                  <h3 class="guest-title mb-1">Manage Guests</h3>
                  <p class="guest-subtitle mb-0">
                    Manage absences for upcoming days
                  </p>
                </div>
              </div>

              <!-- Right Section -->
              <div>
                <a href="{{ route('admin.guests.list', $dayStatus->id) }}" class="btn btn-sm btn-outline-primary">
                  <i class="fa fa-users me-1"></i> Today's Guests
                </a>
              </div>

            </div>

            <div class="row">

              <!-- Personal Guest -->
              <div class="col-6">
                <div class="guest-box personal">

                  <div class="guest-label personal-text">
                    PERSONAL GUEST
                  </div>

                  <div class="guest-count">
                    {{ $personalguestCount }}
                  </div>

                  <div class="guest-limit">
                    of {{ $UserData->max_personal_guest_allowed }} allowed
                  </div>

                  <div class="progress guest-progress">
                    <div class="progress-bar personal-bar"
                      style="width:40%">
                    </div>
                  </div>

                </div>
              </div>

              <!-- Official Guest -->
              <div class="col-6">
                <div class="guest-box official">

                  <div class="guest-label official-text">
                    OFFICIAL GUEST
                  </div>

                  <div class="guest-count text-success">
                    {{ $officeguestCount }}
                  </div>

                  <div class="guest-limit">
                    of {{ $UserData->max_office_guest_allowed }} allowed
                  </div>

                  <div class="progress guest-Mark">
                    <div class="progress-bar official-bar"
                      style="width:55%">
                    </div>
                  </div>

                </div>
              </div>
            </div>
            <div class="mt-4">
              <a href="{{ route('admin.guests.create', $dayStatus->id) }}"
                class="btn btn-undo w-100">
                <i class="fa fa-rotate-left"></i>
                Schedule a Guest Meal
              </a>
            </div>

          </div>
        </div>
      </div>
      @endif

      @if($overrideLock)
      <div class="col-12 col-lg-6 mb-4">
        <div class="card upcoming-card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
              <h3 class="upcoming-title mb-0">Manage Attendance</h3>
              <div class="d-flex align-items-end gap-3 flex-wrap mt-1">
                <div>
                  <label for="member_filter" class="form-label mb-1 fw-semibold">
                    Member / Non Member
                  </label>
                  <select class="form-control  form-select-sm" id="member_filter" style="min-width: 180px;">
                    <option value="">All</option>
                    <option value="Member">Member</option>
                    <option value="Non Member">Non Member</option>
                  </select>
                </div>
                <div>
                  <label for="attendance_filter" class="form-label mb-1 fw-semibold">
                    Present / Absent
                  </label>
                  <select class="form-control form-select-sm" id="attendance_filter" style="min-width: 160px;">
                    <option value="">All</option>
                    <option value="Present">Present</option>
                    <option value="Absent">Absent</option>
                  </select>
                </div>

              </div>
            </div>
            <div class="table-responsive">
              <div style="max-height: 400px; overflow-y: auto;">
                <table class="table table-bordered table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th width="50%">Employee Name</th>
                      <th width="0%">Status</th>
                      <th width="20%">Attendance</th>
                      <th width="30%">Override</th>
                    </tr>
                  </thead>

                  <tbody>

                    @forelse($usersAttendance as $user)
                    <tr
                      data-member="{{ $user->role }}"
                      data-attendance="{{ $user->absent_flag == 1 ? 'Absent' : 'Present' }}">

                      <td>
                        <strong>{{ $user->first_name }}</strong><br>

                        <small class="text-muted">
                          {{ $user->department_name }}
                        </small><br>

                        @if($user->role == 'Member')
                        <span class="badge bg-primary mt-1">Member</span>
                        @else
                        <span class="badge bg-secondary mt-1">Non Member</span>
                        @endif
                      </td>
                      <td>
                        @if($user->absent_flag == 1)
                        <span class="badge bg-danger attendance-badge">Absent</span>
                        @else
                        <span class="badge bg-primary attendance-badge">Present</span>
                        @endif
                      </td>

                      <td>
                        <button
                          class="btn btn-sm attendance-btn {{ $user->absent_flag == 1 ? 'btn-outline-primary' : 'btn-outline-danger' }}"
                          data-user-id="{{ $user->id }}"
                          data-status="{{ $user->absent_flag }}">
                          {{ $user->absent_flag == 1 ? 'Mark Present' : 'Mark Absent' }}
                        </button>
                      </td>

                      {{-- OR --}}
                      {{-- <span class="badge {{ $user->attendance_status == 'Present' ? 'bg-success' : 'bg-danger' }}">
                      {{ $user->attendance_status }}
                      </span> --}}
                      <td>
                        <button
                          class="btn btn-sm btn-outline-warning override-btn"
                          data-bs-toggle="modal"
                          data-bs-target="#overrideModal"
                          data-user-id="{{ $user->id }}"
                          data-user-name="{{ $user->first_name }}"
                          data-department="{{ $user->department_name }}"
                          data-status="{{ $user->absent_flag }}">
                          Override
                        </button>
                      </td>

                    </tr>
                    @empty
                    <tr>
                      <td colspan="3" class="text-center">
                        No Record Found
                      </td>
                    </tr>
                    @endforelse

                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
      </div>
      @endif
    </div>
    @endif

  </div>

  <!-- Today's Menu Modal -->
  <div class="modal fade" id="todayMenuModal" tabindex="-1" aria-labelledby="todayMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content shadow">

        <div class="card-header">
          <h5 class="modal-title" id="todayMenuModalLabel">
            Today's Menu
          </h5>
        </div>

        <div class="modal-body">

          <div class="text-center mb-3">
            <strong>{{ date('l, d M Y') }}</strong>
          </div>

          <table class="table table-bordered mb-0">
            <thead>
              <tr>
                <th width="10%">#</th>
                <th>Today's Menu</th>
              </tr>
            </thead>

            <tbody>
              @foreach($todayMenu as $dailyMenudata)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $dailyMenudata['name'] }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>

        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">
            Close
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- Override Modal -->
  <div class="modal fade" id="overrideModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content border-0 shadow-lg rounded-4">

        <div class="modal-header border-0 pb-0">
          <div>
            <h4 class="card-title fw-bold mb-0">Attendance Override</h4>
            <small class="text-muted">Manually update attendance status</small>
          </div>

          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <input type="hidden" id="override_user_id">

          <!-- User Card -->

          <div class="user-card d-flex align-items-center justify-content-between">

            <div class="d-flex align-items-center">
              <div class="ms-3">
                <h5 class="mb-1 fw-bold" id="userName"></h5>
                <small class="" id="departmentName"></small>
              </div>

            </div>

            <span class="badge rounded-pill px-3 py-2" id="currentStatus">
              Present
            </span>
          </div>

          <h5 class="mt-4 fw-bold">Change Status</h5>

          <div class="status-buttons mt-3">

            <button class="status-btn active" data-value="0">
              Present
            </button>

            <button class="status-btn" data-value="1">
              Absent
            </button>

          </div>

          <div class="mt-4">

            <label class="fw-bold mb-2">
              Reason (Optional)
            </label>

            <textarea
              class="form-control rounded-4"
              rows="4"
              id="overrideRemark"
              placeholder="Enter reason..."></textarea>

          </div>

        </div>

        <div class="modal-footer border-0">

          <button class="btn btn-light rounded-pill px-4"
            data-bs-dismiss="modal">
            Cancel
          </button>

          <button class="btn btn-primary rounded-pill px-4"
            id="saveOverride">
            Confirm
          </button>

        </div>

      </div>
    </div>
  </div>
  <!-- content-wrapper ends -->
  @include('partials.admin.footer')
</div>


<!-- main-panel ends -->

<script>
  let remainingSeconds = {
    {
      (int)($remainingSeconds ?? 0)
    }
  };

  function updateCountdown() {
    const countdown = document.getElementById('countdown');

    if (!countdown) return;

    if (remainingSeconds <= 0) {
      countdown.textContent = "00:00:00";
      clearInterval(timer);
      return;
    }

    const hours = Math.floor(remainingSeconds / 3600);
    const minutes = Math.floor((remainingSeconds % 3600) / 60);
    const seconds = remainingSeconds % 60;

    countdown.textContent =
      String(hours).padStart(2, '0') + ':' +
      String(minutes).padStart(2, '0') + ':' +
      String(seconds).padStart(2, '0');

    remainingSeconds--;
  }

  updateCountdown();
  const timer = setInterval(updateCountdown, 1000);
</script>
@endsection