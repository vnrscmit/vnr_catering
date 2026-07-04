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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


@endpush
@section('title', 'Admin - Dashboard')
@section('content')

<div class="main-panel">
  <div class="content-wrapper">
    @include('partials.message-bag')

    @include('partials.order-stats')

    <div class="row">
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
                    {{ $menu }}
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
                    {{ $menu }}
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

                  <div class="progress guest-progress">
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
      <!-- ================= Override Attendance ================= -->
      <div class="col-12 col-lg-6 mb-4">
        <div class="card upcoming-card">
          <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">
              <h3 class="upcoming-title">
                Override Attendance
              </h3>

              <div class="card-date">
                Admin Access
              </div>
            </div>
            <form action="{{ route('attendance.override') }}" method="POST">
              @csrf
              <div class="mb-3">
                <label class="form-label fw-bold">
                  Select Employee <span class="text-danger">*</span>
                </label>
                <select name="user_id" class="form-control" required>
                  <option value="">Select Employee</option>
                  @foreach($allusers as $user)
                  <option value="{{ $user->id }}">
                    {{ $user->first_name }}
                  </option>
                  @endforeach
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold">
                  Attendance Date <span class="text-danger">*</span>
                </label>

                <input type="date"
                  name="attendance_date"
                  class="form-control"
                  min="{{ date('Y-m-d') }}"
                  value="{{ date('Y-m-d') }}"
                  required>
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold">
                  Attendance Status <span class="text-danger">*</span>
                </label>

                <select name="status" class="form-control" required>
                  <option value="">Select Status</option>
                  <option value="1">Present</option>
                  <option value="0">Absent</option>
                </select>
              </div>

              <div class="mb-4">
                <label class="form-label fw-bold">
                  Remarks
                </label>

                <textarea class="form-control"
                  name="remarks"
                  rows="3"
                  placeholder="Enter reason for overriding attendance..."></textarea>
              </div>

              <button class="btn btn-undo">
                <i class="fa fa-edit me-2"></i>
                Override Attendance
              </button>

            </form>

          </div>
        </div>
      </div>
      @endif
    </div>


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
                        <tr>
                            <td>1</td>
                            <td>Dal Fry</td>
                        </tr>

                        <tr>
                            <td>2</td>
                            <td>Jeera Rice</td>
                        </tr>

                        <tr>
                            <td>3</td>
                            <td>Mix Veg</td>
                        </tr>

                        <tr>
                            <td>4</td>
                            <td>Chapati</td>
                        </tr>

                        <tr>
                            <td>5</td>
                            <td>Salad</td>
                        </tr>

                        <tr>
                            <td>6</td>
                            <td>Sweet (Gulab Jamun)</td>
                        </tr>
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