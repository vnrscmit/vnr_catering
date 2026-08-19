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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function resetFormWithSweetAlert() {
        Swal.fire({
            title: 'Are you sure?',
            text: "All form fields will be cleared!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, reset it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.querySelector('form').reset();
                Swal.fire(
                    'Reset!',
                    'All fields have been reset successfully.',
                    'success'
                );
            }
        });
    }

    $(document).ready(function() {

        const maxLength = 255;
        const description = $('#description');
        const counter = $('#descriptionCount');

        function updateDescriptionCount() {
            const currentLength = description.val().length;
            const remaining = maxLength - currentLength;

            counter.text(remaining);

            if (remaining <= 50) {
                counter.removeClass('text-danger').addClass('text-danger');
            } else {
                counter.removeClass('text-danger').addClass('text-danger');
            }
        }

        description.on('input', function() {
            updateDescriptionCount();
        });

        // Handle old value after validation error
        updateDescriptionCount();
    });
</script>
@endpush

@section('title', 'Create Feedback')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fa fa-comment-dots menu-icon"></i> Feedback
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('feedback.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        <!-- Subject Selection -->
                        <div class="mb-3 col-8">
                            <label for="subject" class="form-label">
                                Subject <span class="text-danger">*</span>
                            </label>
                            <select class="form-control form-select @error('subject') is-invalid @enderror"
                                id="subject"
                                name="subject"
                                required>
                                <option value="">Select Subject</option>
                                <option value="Ahaar App" {{ old('subject') == 'Ahaar App' ? 'selected' : '' }}>Ahaar App</option>
                                <option value="Attendance" {{ old('subject') == 'Attendance' ? 'selected' : '' }}>Attendance</option>
                                <option value="Billing" {{ old('subject') == 'Billing' ? 'selected' : '' }}>Billing</option>
                                <option value="Food Menu" {{ old('subject') == 'Food Menu' ? 'selected' : '' }}>Food Menu</option>
                                <option value="Food Quality" {{ old('subject') == 'Food Quality' ? 'selected' : '' }}>Food Quality</option>
                                <option value="Service" {{ old('subject') == 'Service' ? 'selected' : '' }}>Service</option>
                                <option value="Staff" {{ old('subject') == 'Staff' ? 'selected' : '' }}>Staff</option>
                                <option value="Others" {{ old('subject') == 'Others' ? 'selected' : '' }}>Others</option>
                            </select>
                            @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-3 col-12">
                            <label for="description" class="form-label">
                                Description <span class="text-danger">*</span>
                            </label>

                            <textarea
                                class="form-control @error('description') is-invalid @enderror"
                                id="description"
                                name="description"
                                rows="5"
                              maxlength="255"
                                placeholder="Please describe your feedback in detail..."
                                required>{{ old('description') }}</textarea>

                            <div class="d-flex justify-content-end mt-1">
                                <small class="text-danger"
                                    style="font-size: 97% !important; font-weight: 400 !important;">
                                    <span id="descriptionCount">255</span> Characters Remaining
                                </small>
                            </div>

                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-end">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Submit
                            </button>

                            <button type="reset" class="btn" onclick="resetFormWithSweetAlert()" style="background-color: #117a8b; border-color: #10707f; color: white;">
                                <i class="fa fa-undo"></i> Reset
                            </button>
                            <a href="{{ route('feedback.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection