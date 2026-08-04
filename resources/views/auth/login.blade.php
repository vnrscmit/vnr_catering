@extends('layouts.main-site')

@push('styles')

<!-- Animation CSS -->
<link rel="stylesheet" href="/assets/css/animate.css">
<!-- Latest Bootstrap min CSS -->
<link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
<!-- Google Font -->
<link href="https://fonts.googleapis.com/css?family=Kaushan+Script&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Josefin+Sans:100,100i,300,300i,400,400i,600,600i,700,700i&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Inter:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i&amp;display=swap" rel="stylesheet">
<!-- Icon Font CSS -->
<link rel="stylesheet" href="/assets/css/all.min.css">
<link rel="stylesheet" href="/assets/css/ionicons.min.css">
<link rel="stylesheet" href="/assets/css/themify-icons.css">
<link rel="stylesheet" href="/assets/css/linearicons.css">
<link rel="stylesheet" href="/assets/css/flaticon.css">
<!--- owl carousel CSS-->
<link rel="stylesheet" href="/assets/owlcarousel/css/owl.carousel.min.css">
<link rel="stylesheet" href="/assets/owlcarousel/css/owl.theme.css">
<link rel="stylesheet" href="/assets/owlcarousel/css/owl.theme.default.min.css">
<!-- Slick CSS -->
<link rel="stylesheet" href="/assets/css/slick.css">
<link rel="stylesheet" href="/assets/css/slick-theme.css">
<!-- Magnific Popup CSS -->
<link rel="stylesheet" href="/assets/css/magnific-popup.css">
<!-- DatePicker CSS -->
<link href="/assets/css/datepicker.min.css" rel="stylesheet">
<!-- TimePicker CSS -->
<link href="/assets/css/mdtimepicker.min.css" rel="stylesheet">
<!-- Style CSS -->
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/responsive.css">
<link id="layoutstyle" rel="stylesheet" href="/assets/color/theme-red.css">
@endpush

@push('scripts')

<!-- Latest jQuery -->
<script src="/assets/js/jquery-1.12.4.min.js"></script>
<!-- Latest compiled and minified Bootstrap -->
<script src="/assets/bootstrap/js/bootstrap.min.js"></script>
<!-- owl-carousel min js  -->
<script src="/assets/owlcarousel/js/owl.carousel.min.js"></script>
<!-- magnific-popup min js  -->
<script src="/assets/js/magnific-popup.min.js"></script>
<!-- waypoints min js  -->
<script src="/assets/js/waypoints.min.js"></script>
<!-- parallax js  -->
<script src="/assets/js/parallax.js"></script>
<!-- countdown js  -->
<script src="/assets/js/jquery.countdown.min.js"></script>
<!-- jquery.countTo js  -->
<script src="/assets/js/jquery.countTo.js"></script>
<!-- imagesloaded js -->
<script src="/assets/js/imagesloaded.pkgd.min.js"></script>
<!-- isotope min js -->
<script src="/assets/js/isotope.min.js"></script>
<!-- jquery.appear js  -->
<script src="/assets/js/jquery.appear.js"></script>
<!-- jquery.dd.min js -->
<script src="/assets/js/jquery.dd.min.js"></script>
<!-- slick js -->
<script src="/assets/js/slick.min.js"></script>
<!-- DatePicker js -->
<script src="/assets/js/datepicker.min.js"></script>
<!-- TimePicker js -->
<script src="/assets/js/mdtimepicker.min.js"></script>
<!-- scripts js -->
<script src="/assets/js/scripts.js"></script>

<script src="https://js.stripe.com/v3/"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        $('.toggle-password').on('click', function() {
            const input = $('#password');
            const icon = $(this);
            const type = input.attr('type') === 'password' ? 'text' : 'password';
            input.attr('type', type);
            icon.toggleClass('fa-eye fa-eye-slash');
        });
    });
</script>

@if(session('showOtpModal'))
<script>
    document.addEventListener('DOMContentLoaded', function() {

        document.getElementById('otp_email').value = "{{ session('email') }}";

        var forgotModal = bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal'));
        if (forgotModal) {
            forgotModal.hide();
        }

        var otpModal = new bootstrap.Modal(document.getElementById('verifyOtpModal'));
        otpModal.show();
        startOtpTimer();
    });

    let duration = 120;
    let timerInterval;

    function startOtpTimer() {

        clearInterval(timerInterval);

        duration = 120;

        timerInterval = setInterval(function() {

            let minutes = Math.floor(duration / 60);
            let seconds = duration % 60;

            document.getElementById('otpTimer').innerHTML =
                String(minutes).padStart(2, '0') + ":" +
                String(seconds).padStart(2, '0');

            if (duration <= 0) {

                clearInterval(timerInterval);

                document.getElementById('otpTimer').style.display = "none";

                document.getElementById('otpExpired').style.display = "block";

                document.querySelector("#verifyOtpModal button[type='submit']")
                    .disabled = true;

            }

            duration--;

        }, 1000);

    }
</script>
@endif

@if(session('showResetModal'))
<script>
    document.addEventListener('DOMContentLoaded', function() {

        var otpModal = bootstrap.Modal.getInstance(document.getElementById('verifyOtpModal'));

        if (otpModal) {
            otpModal.hide();
        }

        var resetModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));

        resetModal.show();
    });
</script>
@endif

@endpush

@section('title', 'Login')

@section('content')

<style>
    /* .login-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 15px;
        background:
            url('{{ asset('assets/images/ahaar_bg_login.png') }}') center/cover no-repeat;
    } */

    .login-page {
        min-height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 15px;

        background-image: url('{{ asset("assets/images/ahaar_bg_login.png") }}');
        background-repeat: no-repeat;
        background-position: center center;
        background-size: cover;
        background-color: #f8f8f2;
    }



    .login-card {
        background: rgba(255, 255, 255, 0.97);
        border-radius: 24px;
        padding: 36px 32px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.24);
        border: 1px solid rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(10px);
    }

    .login-card .form-control {
        border-radius: 12px;
        padding: 12px 14px;
        border: 1px solid #e0e0e0;
        transition: all 0.3s ease;
    }

    .login-card .form-control:focus {
        border-color: #4E7E24;
    }

    .login-card .input-group-text {
        border-radius: 0 12px 12px 0;
        background: #fff;
        cursor: pointer;
    }

    .login-title {
        font-size: 28px;
        font-weight: 700;
        color: #2b2b2b;
        margin-bottom: 8px;
    }

    .login-subtitle {
        color: #777;
        margin-bottom: 24px;
    }

    .login-btn {
        border: none;
        border-radius: 12px;
        padding: 12px 16px;
        font-weight: 600;
        background: linear-gradient(135deg, #265A16, #2A5E16);
        color: #fff;
    }

    .login-btn:hover {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(64, 103, 30, 0.35);
    }

    @media (max-width: 767px) {
        .login-card {
            padding: 24px 18px;
        }

        .login-title {
            font-size: 24px;
        }
    }
</style>

<div class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-5">
                <div class="login-card">
                    <form method="post" action="{{ route('auth.login.process') }}">
                        @csrf
                        <div class="text-center mb-4">
                            <img src="{{ url('assets/images/ahaar_logo_login_3.png') }}"
                                alt="Logo"
                                width="110">
                        </div>

                        <div class="text-center mb-4">
                            <h3 class="login-title">Welcome Back</h3>
                            <p class="login-subtitle">Sign in to continue to your account</p>
                        </div>

                        @include('partials.message-bag')

                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="username">Username</label>
                                <input id="username" class="form-control" placeholder="Enter your username" required type="text" name="username" value="{{ old('username') }}">
                            </div>

                            <div class="form-group col-md-12 position-relative">
                                <label for="password">Password</label>
                                <div class="input-group">
                                    <input id="password" class="form-control" placeholder="Enter your password" required type="password" name="password">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <i class="fas fa-eye toggle-password" style="cursor:pointer;"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-end mt-2 mb-3">
                                <a href="#"
                                    class="text-primary text-decoration-none"
                                    data-bs-toggle="modal"
                                    data-bs-target="#forgotPasswordModal">
                                    Forgot Password?
                                </a>
                            </div>

                            <div class="form-group mb-0 mt-3 col-md-12">
                                <button type="submit" class="btn btn-block login-btn">Login</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">

            <div class="modal-header">
                <h5 class="modal-title" id="forgotPasswordModalLabel">
                    Forgot Password
                </h5>

                <button type="button" class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('password.sendOtp') }}">
                @csrf

                <div class="modal-body">

                    <p class="text-muted">
                        Enter your registered email address. We'll send you a OTP.
                    </p>

                    <div class="mb-3">
                        <label>Email Address <span class="text-danger">*</span></label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="Enter your email"
                            required>
                    </div>

                </div>

                <div class="modal-footer">

                    <!-- <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                        Cancel
                    </button> -->

                    <button type="submit"
                        class="btn login-btn">
                        Send OTP
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<!-- Verify OTP Modal -->
<div class="modal fade" id="verifyOtpModal" tabindex="-1" aria-labelledby="verifyOtpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">

            <div class="modal-header">
                <h5 class="modal-title" id="verifyOtpModalLabel">
                    Verify OTP
                </h5>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('password.verifyOtp') }}">
                @csrf

                <div class="modal-body">

                    <p class="text-muted">
                        Enter the 6-digit OTP sent to your registered email.
                    </p>

                    <!-- Email -->
                    <input type="hidden" name="email" id="otp_email">

                    <!-- OTP -->
                    <div class="mb-3">
                        <label>OTP <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="otp"
                            class="form-control"
                            placeholder="Enter 6-digit OTP"
                            maxlength="6"
                            required>
                    </div>

                    <div class="mb-3 text-center">
                        <span id="otpTimer" class="fw-bold text-success">
                            02:00
                        </span>

                        <div id="otpExpired"
                            class="text-danger fw-bold mt-2"
                            style="display:none;">
                            OTP has expired.
                        </div>
                    </div>

                </div>

                <div class="modal-footer d-flex justify-content-end">

                    <button type="submit"
                        class="btn login-btn">
                        Verify OTP
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>


<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">

            <div class="modal-header">
                <h5 class="modal-title">
                    Reset Password
                </h5>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="{{ route('password.updatePassword') }}">
                @csrf

                <input type="hidden"
                    name="user_id"
                    value="{{ session('user_id') }}">

                <div class="modal-body">

                    <div class="mb-3">
                        <label>New Password <span class="text-danger">*</span></label>

                        <input type="password"
                            name="password"
                            class="form-control"
                            required>
                    </div>

                    <div class="mb-3">
                        <label>Confirm Password <span class="text-danger">*</span></label>
                        <input type="password"
                            name="password_confirmation"
                            class="form-control"
                            required>
                    </div>

                </div>

                <div class="modal-footer">

                    <button type="submit"
                        class="btn login-btn">
                        Update Password
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

@endsection