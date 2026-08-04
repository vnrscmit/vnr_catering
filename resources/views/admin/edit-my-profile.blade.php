@extends('layouts.admin')

@push('styles')
    <!-- base:css -->
    <link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
    <link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
    
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    
    <style>
        .cropper-container {
            max-height: 400px;
            width: 100%;
        }
        #image-to-crop {
            max-width: 100%;
            max-height: 100%;
        }
        .modal-body .cropper-container {
            max-height: 450px;
        }
        .crop-preview {
            overflow: hidden;
            border-radius: 50%;
            border: 3px solid #ddd;
        }
        .crop-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-image-wrapper {
            position: relative;
            display: inline-block;
        }
        .profile-image-wrapper .edit-icon {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: #406A2C;
            color: white;
            border-radius: 50%;
            padding: 8px;
            font-size: 14px;
            cursor: pointer;
            border: 2px solid white;
        }
        .profile-image-wrapper .edit-icon:hover {

        }
    </style>
@endpush

@push('scripts')
    <script src="/admin_resources/vendors/js/vendor.bundle.base.js"></script>
    <script src="/admin_resources/js/off-canvas.js"></script>
    <script src="/admin_resources/js/hoverable-collapse.js"></script>
    <script src="/admin_resources/js/template.js"></script>
    <script src="/admin_resources/js/settings.js"></script>
    <script src="/admin_resources/js/todolist.js"></script>
    <script src="/admin_resources/vendors/progressbar.js/progressbar.min.js"></script>
    <script src="/admin_resources/vendors/chart.js/Chart.min.js"></script>
    <script src="/admin_resources/js/dashboard.js"></script>

    <!-- jQuery, Bootstrap, and Cropper.js -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <script type="text/javascript">
        var cropper = null;
        var croppedImageData = null;

        // Handle file input change
        function previewImage() {
            var file = document.getElementById('profile_photo').files[0];
            var reader = new FileReader();
            
            reader.onloadend = function () {
                // Show the image in the crop modal
                var image = document.getElementById('image-to-crop');
                image.src = reader.result;
                
                // Show the crop modal
                var cropModal = new bootstrap.Modal(document.getElementById('cropModal'));
                cropModal.show();
                
                // Initialize cropper after modal is shown
                setTimeout(function() {
                    if (cropper) {
                        cropper.destroy();
                    }
                    cropper = new Cropper(image, {
                        aspectRatio: 1, // 1:1 for profile picture
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 0.8,
                        cropBoxResizable: true,
                        cropBoxMovable: true,
                        guides: true,
                        center: true,
                        highlight: true,
                        background: false,
                        responsive: true,
                        zoomable: true,
                        zoomOnTouch: true,
                        zoomOnWheel: true
                    });
                }, 100);
            };
            
            if (file) {
                reader.readAsDataURL(file);
            }
        }

        // Crop and upload image
        function cropImage() {
            if (cropper) {
                // Get cropped image as canvas
                var canvas = cropper.getCroppedCanvas({
                    width: 300,
                    height: 300,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                });
                
                // Convert to base64
                croppedImageData = canvas.toDataURL('image/jpeg', 0.95);
                
                // Close modal
                var cropModal = bootstrap.Modal.getInstance(document.getElementById('cropModal'));
                cropModal.hide();
                
                // Update preview
                document.getElementById('profile_preview').src = croppedImageData;
                
                // Store cropped image in hidden input
                document.getElementById('cropped_image').value = croppedImageData;
                
                // Clear the file input
                document.getElementById('profile_photo').value = '';
            }
        }

        // Remove cropped image
        function removeImage() {
            if (confirm('Are you sure you want to remove this profile picture?')) {
                document.getElementById('profile_preview').src = "{{ asset('assets/images/user-icon.png') }}";
                document.getElementById('cropped_image').value = '';
                document.getElementById('profile_photo').value = '';
                croppedImageData = null;
            }
        }

        // Reset cropper on modal close
        document.addEventListener('hidden.bs.modal', function (event) {
            if (event.target.id === 'cropModal') {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                document.getElementById('image-to-crop').src = '';
            }
        });

        // Preview the selected image (Profile Photo) - Auto trigger crop modal
        $(document).ready(function() {
            // Add a remove button event listener
            $('#remove_image_btn').on('click', removeImage);
        });
    </script>
@endpush

@section('title', 'Admin - Edit Profile')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        @include('partials.message-bag')

        <form action="{{ route('admin.myprofile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <i class="fa fa-user"></i>&nbsp; Edit Profile
                </div>
                <div class="card-body">
                    <div class="card-body d-flex justify-content-center align-items-center">
                        <!-- Profile Photo Preview -->
                        <div class="mb-3 text-center">
                            <div class="profile-image-wrapper">
                                <img id="profile_preview" 
                                     src="{{ $user->profile_picture ? asset('storage/profile-picture/' . $user->profile_picture) : asset('assets/images/user-icon.png') }}" 
                                     alt="Profile Preview" 
                                     class="img-thumbnail" 
                                     style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%;">
                                <div class="edit-icon" onclick="document.getElementById('profile_photo').click();">
                                    <i class="fa fa-camera"></i>
                                </div>
                            </div>
                            <br/>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-danger" id="remove_image_btn">
                                    <i class="fa fa-trash"></i> Remove
                                </button>
                            </div>
                            <input type="file" class="d-none" id="profile_photo" name="profile_photo" accept="image/*" onchange="previewImage()">
                            <input type="hidden" id="cropped_image" name="cropped_image" value="">
                            <small class="text-muted d-block mt-2">Click the camera icon to upload a new photo</small>
                        </div>
                    </div>

                    <hr/>
                    
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td width="30%">
                                    <label for="first_name">Name</label>
                                </td>
                                <td>
                                    <input type="text"
                                        class="form-control"
                                        id="first_name"
                                        name="first_name"
                                        value="{{ old('first_name', $user->first_name) }}"
                                        required>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="email">Email</label>
                                </td>
                                <td>
                                    <input type="email"
                                        class="form-control"
                                        id="email"
                                        name="email"
                                        value="{{ old('email', $user->email) }}"
                                        required>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="mobile">Mobile</label>
                                </td>
                                <td>
                                    <input type="text"
                                        class="form-control"
                                        id="mobile"
                                        name="mobile"
                                        maxlength="10"
                                        value="{{ old('mobile', $user->mobile) }}">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="designation">Designation</label>
                                </td>
                                <td>
                                    <input type="text"
                                        class="form-control"
                                        id="designation"
                                        name="designation"
                                        value="{{ old('designation', $user->designation) }}">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                    <button type="button" onclick="window.location='{{ route('admin.view.myprofile') }}'" class="btn btn-secondary float-right">Back</button>
                </div>
            </div>
        </form>
    </div>
    <!-- content-wrapper ends -->
    @include('partials.admin.footer')
</div>
<!-- main-panel ends -->

<!-- Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cropModalLabel">Crop Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="crop-container">
                            <img id="image-to-crop" src="" alt="Picture to crop">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-center">
                            <label class="fw-bold">Preview</label>
                            <div class="crop-preview" style="width: 150px; height: 150px; margin: 10px auto; border: 3px solid #ddd; border-radius: 50%; overflow: hidden;">
                                <img id="crop-preview-image" src="" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="resetCropper()">
                                    <i class="fa fa-undo"></i> Reset
                                </button>
                                <button type="button" class="btn btn-sm btn-info" onclick="rotateCropper(-90)">
                                    <i class="fa fa-arrow-left"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-info" onclick="rotateCropper(90)">
                                    <i class="fa fa-arrow-right"></i>
                                </button>
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">Drag to move<br>Scroll to zoom</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-crop" onclick="cropImage()">
                    <i class="fa fa-crop"></i> Crop & Upload
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Additional functions for crop controls
    function resetCropper() {
        if (cropper) {
            cropper.reset();
            cropper.setCropBoxData({ left: 0, top: 0, width: 80, height: 80 });
        }
    }

    function rotateCropper(degrees) {
        if (cropper) {
            cropper.rotate(degrees);
        }
    }

    // Update preview when cropping
    document.addEventListener('DOMContentLoaded', function() {
        // Listen for crop event to update preview
        document.addEventListener('cropend', function() {
            if (cropper) {
                var canvas = cropper.getCroppedCanvas({ width: 150, height: 150 });
                document.getElementById('crop-preview-image').src = canvas.toDataURL('image/jpeg');
            }
        });
    });

    // Override cropImage to update preview
    var originalCropImage = cropImage;
    cropImage = function() {
        if (cropper) {
            // Update preview before closing
            var canvas = cropper.getCroppedCanvas({ width: 150, height: 150 });
            document.getElementById('crop-preview-image').src = canvas.toDataURL('image/jpeg');
            
            // Call original function
            originalCropImage();
        }
    };

    // Update preview on crop box change
    $(document).ready(function() {
        // Add crop event listener
        $('#cropModal').on('shown.bs.modal', function() {
            // Initialize crop preview
            var cropBoxData = cropper.getCropBoxData();
            var canvas = cropper.getCroppedCanvas({ width: 150, height: 150 });
            document.getElementById('crop-preview-image').src = canvas.toDataURL('image/jpeg');
        });
    });
</script>
@endsection