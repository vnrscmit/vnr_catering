@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />

<style>
    .organization-card {
        display: none;
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .organization-card.show {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .detail-item {
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .detail-item:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: 600;
        color: #495057;
        width: 120px;
        display: inline-block;
    }

    .detail-value {
        color: #212529;
    }

    .section-header {
        font-size: 16px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }

    .section-header i {
        margin-right: 10px;
        color: #1A8C39;
    }

    .required-star {
        color: #dc3545;
        margin-left: 3px;
    }

    .text-muted-small {
        font-size: 12px;
        color: #6c757d;
    }

    .divider {
        border-top: 1px solid #e9ecef;
        margin: 20px 0;
    }

    .preview-container {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        background: #f8f9fa;
    }

    .preview-container img {
        max-width: 150px;
        max-height: 150px;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    .preview-container .placeholder-text {
        color: #6c757d;
    }

    /* Crop Modal Styles */
    .crop-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }

    .crop-modal-overlay.active {
        display: flex;
    }

    .crop-modal-content {
        background: white;
        border-radius: 8px;
        padding: 20px;
        max-width: 90%;
        max-height: 90%;
        width: 800px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .crop-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e9ecef;
    }

    .crop-modal-header h5 {
        margin: 0;
        color: #2c3e50;
    }

    .crop-modal-body {
        position: relative;
        max-height: 500px;
        overflow: hidden;
    }

    .crop-modal-body img {
        max-width: 100%;
        display: block;
    }

    .crop-modal-footer {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e9ecef;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .crop-preview-container {
        display: none;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 10px;
        margin-top: 10px;
        background: #f8f9fa;
    }

    .crop-preview-container.active {
        display: block;
    }

    .crop-preview-container img {
        max-width: 150px;
        max-height: 150px;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    /* Status Badge Styles */
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-badge.active {
        background: #d4edda;
        color: #155724;
    }

    .status-badge.inactive {
        background: #f8d7da;
        color: #721c24;
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize select2 for all select elements
        // $('.select2, #state, #district, #tehsil').select2({
        //     width: '100%',
        //     placeholder: 'Select an option',
        //     allowClear: true
        // });

        // Crop variables
        let cropper = null;
        let currentFile = null;

        // State change - Load Districts
        $('#state').on('change', function() {
            var stateId = $(this).val();
            var districtSelect = $('#district');
            var tehsilSelect = $('#tehsil');

            // Reset district and tehsil selects
            districtSelect.empty().append('<option value="">-- Select District --</option>');
            tehsilSelect.empty().append('<option value="">-- Select Tehsil --</option>');
            tehsilSelect.prop('disabled', true);

            if (stateId) {
                $.ajax({
                    url: "{{ route('get.districts') }}",
                    type: "GET",
                    data: {
                        state_id: stateId
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 'success') {
                            $.each(data.districts, function(key, district) {
                                districtSelect.append('<option value="' + district.id + '">' +
                                    district.name + '</option>');
                            });
                            districtSelect.prop('disabled', false);
                            // Refresh select2
                            districtSelect.trigger('change');
                        } else {
                            districtSelect.prop('disabled', true);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching districts:', error);
                        showToast('error', 'Failed to load districts. Please try again.');
                    }
                });
            } else {
                districtSelect.prop('disabled', true);
                districtSelect.trigger('change');
            }
        });

        // District change - Load Tehsils
        $('#district').on('change', function() {
            var districtId = $(this).val();
            var tehsilSelect = $('#tehsil');

            // Reset tehsil select
            tehsilSelect.empty().append('<option value="">-- Select Tehsil --</option>');

            if (districtId) {
                $.ajax({
                    url: "{{ route('get.tehsils') }}",
                    type: "GET",
                    data: {
                        district_id: districtId
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 'success') {
                            $.each(data.tehsils, function(key, tehsil) {
                                tehsilSelect.append('<option value="' + tehsil.id + '">' +
                                    tehsil.name + '</option>');
                            });
                            tehsilSelect.prop('disabled', false);
                            // Refresh select2
                            tehsilSelect.trigger('change');
                        } else {
                            tehsilSelect.prop('disabled', true);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching tehsils:', error);
                        showToast('error', 'Failed to load tehsils. Please try again.');
                    }
                });
            } else {
                tehsilSelect.prop('disabled', true);
                tehsilSelect.trigger('change');
            }
        });

        // Logo upload with crop functionality
        $('#logo').change(function() {
            const file = this.files[0];
            if (file) {
                // Check if file is an image
                if (!file.type.startsWith('image/')) {
                    showToast('error', 'Please select an image file.');
                    this.value = '';
                    return;
                }

                // Check file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    showToast('error', 'File size must be less than 2MB.');
                    this.value = '';
                    return;
                }

                currentFile = file;
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#crop-image').attr('src', e.target.result);
                    $('#cropModal').addClass('active');

                    // Initialize cropper after image loads
                    setTimeout(function() {
                        if (cropper) {
                            cropper.destroy();
                        }
                        cropper = new Cropper(document.getElementById('crop-image'), {
                            aspectRatio: 1, // Square crop for logo
                            viewMode: 1,
                            autoCropArea: 0.8,
                            dragMode: 'move',
                            background: true,
                            responsive: true,
                            restore: false,
                            center: true,
                            highlight: true,
                            cropBoxMovable: true,
                            cropBoxResizable: true,
                            toggleDragModeOnDblclick: false,
                            minContainerWidth: 300,
                            minContainerHeight: 300,
                        });
                    }, 100);
                };
                reader.readAsDataURL(file);
            }
        });

        // Crop and save
        $('#cropButton').click(function() {
            if (cropper) {
                // Get cropped canvas
                const canvas = cropper.getCroppedCanvas({
                    width: 300,
                    height: 300,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });

                if (canvas) {
                    // Convert canvas to blob
                    canvas.toBlob(function(blob) {
                        // Create a new file from blob
                        const croppedFile = new File([blob], currentFile.name, {
                            type: currentFile.type,
                        });

                        // Update the file input with cropped file
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(croppedFile);
                        document.getElementById('logo').files = dataTransfer.files;

                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $('#logo-preview').attr('src', e.target.result).show();
                            $('#logo-placeholder').hide();
                            $('#logo-preview-container').show();
                        };
                        reader.readAsDataURL(croppedFile);

                        // Close modal
                        closeCropModal();

                        // Show success message
                        showToast('success', 'Image cropped successfully!');
                    }, currentFile.type, 0.9);
                }
            }
        });

        // Cancel crop
        $('#cancelCropButton, .crop-modal-overlay').click(function(e) {
            if (e.target === this || $(this).is('#cancelCropButton')) {
                closeCropModal();
                // Reset file input
                $('#logo').val('');
                $('#logo-preview').hide();
                $('#logo-placeholder').show();
                $('#logo-preview-container').hide();
            }
        });

        // Close crop modal function
        function closeCropModal() {
            $('#cropModal').removeClass('active');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }

        // Toast notification function (simple)
        function showToast(type, message) {
            // You can replace this with a proper toast library like Toastr
            alert(message);
        }

        // Auto-uppercase GSTIN
        $('#gstin').on('input', function() {
            this.value = this.value.toUpperCase();
        });

        // Trigger change on state if there's old value
        @if(old('state'))
        $('#state').val('{{ old('
            state ') }}').trigger('change');

        // If district also has old value, load it
        @if(old('district'))
        setTimeout(function() {
            $('#district').val('{{ old('
                district ') }}').trigger('change');
        }, 500);
        @endif
        @endif

        // Form reset handler
        $('#organizationForm').on('reset', function() {
            setTimeout(function() {
                $('#logo-preview').hide();
                $('#logo-placeholder').show();
                $('#logo-preview-container').hide();
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                $('#state, #district, #tehsil').val('').trigger('change');
            }, 100);
        });
    });
</script>
@endpush

@section('title', 'Create Organization')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            @include('partials.message-bag')

            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-building me-2"></i> Create New Organization
                    </h5>
                    <a href="{{ route('organizations.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="card-body">
                <form action="{{ route('organizations.store') }}" method="POST" enctype="multipart/form-data" id="organizationForm">
                    @csrf

                    <!-- Basic Information Section -->
                    <div class="organization-card show">
                        <h6 class="section-header">
                            <i class="fas fa-info-circle"></i> Basic Information
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="organization_name">
                                    Organization Name <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('organization_name') is-invalid @enderror"
                                    id="name"
                                    name="organization_name"
                                    value="{{ old('organization_name') }}"
                                    placeholder="Enter organization name"
                                    required>
                                @error('organization_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="short_name">
                                    Common Name <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('short_name') is-invalid @enderror"
                                    id="short_name"
                                    name="short_name"
                                    value="{{ old('short_name') }}"
                                    placeholder="e.g., ABC Corp"
                                    required>
                                <small class="text-muted-small">Common name or abbreviation for the organization</small>
                                @error('short_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>


                        </div>
                    </div>

                    <!-- Location Details Section -->
                    <div class="organization-card show">
                        <h6 class="section-header">
                            <i class="fas fa-map-marker-alt"></i> Address Details
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="state">
                                    State <span class="text-danger">*</span>
                                </label>
                                <select class="form-control form-select" id="state" name="state" required>
                                    <option value="">-- Select State --</option>
                                    @foreach($states as $state)
                                    <option value="{{ $state->id }}"
                                        {{ old('state') == $state->id ? 'selected' : '' }}>
                                        {{ $state->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="district">
                                    District <span class="text-danger">*</span>
                                </label>
                                <select class="form-control form-select" id="district" name="district" required>
                                    <option value="">-- Select District --</option>
                                    @if(old('district'))
                                    <option value="{{ old('district') }}" selected>{{ old('district_name') ?? '' }}</option>
                                    @endif
                                </select>
                                @error('district')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="tehsil">
                                    Tehsil
                                </label>
                                <select class="form-control form-select" id="tehsil" name="tehsil">
                                    <option value="">-- Select Tehsil --</option>
                                    @if(old('tehsil'))
                                    <option value="{{ old('tehsil') }}" selected>{{ old('tehsil_name') ?? '' }}</option>
                                    @endif
                                </select>
                                <small class="text-muted-small">Optional field</small>
                                @error('tehsil')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="city_village">
                                    City/Village <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('city_village') is-invalid @enderror"
                                    id="city_village"
                                    name="city_village"
                                    value="{{ old('city_village') }}"
                                    placeholder="Enter city or village"
                                    required>
                                @error('city_village')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="pincode">
                                    Pincode <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('pincode') is-invalid @enderror"
                                    id="pincode"
                                    name="pincode"
                                    value="{{ old('pincode') }}"
                                    placeholder="Enter pincode"
                                    maxlength="10"
                                    required>
                                <small class="text-muted-small">6-10 digit pincode</small>
                                @error('pincode')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="address">
                                    Address <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control @error('address') is-invalid @enderror"
                                    id="address"
                                    name="address"
                                    rows="3"
                                    placeholder="Enter complete address"
                                    required>{{ old('address') }}</textarea>
                                @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

              

                    <!-- Additional Information Section -->
                    <div class="organization-card show">
                        <h6 class="section-header">
                            <i class="fas fa-plus-circle"></i> Additional Information
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="gstin">
                                    GSTIN
                                </label>
                                <input type="text"
                                    class="form-control @error('gstin') is-invalid @enderror"
                                    id="gstin"
                                    name="gstin"
                                    value="{{ old('gstin') }}"
                                    placeholder="e.g., 22AAAAA0000A1Z5"
                                    maxlength="15">
                                <small class="text-muted-small">Format: 22AAAAA0000A1Z5 (15 characters)</small>
                                @error('gstin')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="logo">
                                    Organization Logo
                                </label>
                                <input type="file"
                                    class="form-control @error('logo') is-invalid @enderror"
                                    id="logo"
                                    name="logo"
                                    accept="image/*">
                                <small class="text-muted-small">Allowed: jpeg, png, jpg, gif (Max: 2MB)</small>
                                @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <div class="preview-container" id="logo-preview-container" style="display: none;">
                                    <img id="logo-preview" src="#" alt="Logo Preview">
                                </div>
                                <div class="preview-container" id="logo-placeholder">
                                    <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                    <p class="placeholder-text">No logo uploaded yet</p>
                                </div>
                            </div>

                         
                        </div>
                    </div>

                          <!-- Configuration & Settings Section -->
                    <div class="organization-card show">
                        <h6 class="section-header">
                            <i class="fas fa-cogs"></i> Configuration & Settings
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="max_location_allowed">
                                    Max Locations Allowed <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                    class="form-control @error('max_location_allowed') is-invalid @enderror"
                                    id="max_location_allowed"
                                    name="max_location_allowed"
                                    value="{{ old('max_location_allowed', 1) }}"
                                    min="1"
                                    required>
                                <small class="text-muted-small">Maximum number of locations this organization can have</small>
                                @error('max_location_allowed')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="max_user_per_location">
                                    Total Number of Users <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                    class="form-control @error('max_user_per_location') is-invalid @enderror"
                                    id="max_user_per_location"
                                    name="max_user_per_location"
                                    value="{{ old('max_user_per_location', 10) }}"
                                    min="1"
                                    required>
                                <small class="text-muted-small">Total Number of Users Allowed</small>
                                @error('max_user_per_location')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                               <div class="col-12 mb-3">
                                <label class="form-label">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex align-items-center gap-4">
                                    <div class="form-check me-4">
                                        <input class="form-check-input" type="radio"
                                            name="status" id="status_active" value="1"
                                            {{ old('status', '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="status_active">
                                            <span class="status-badge active">
                                                Active
                                            </span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio"
                                            name="status" id="status_inactive" value="0"
                                            {{ old('status') == '0' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="status_inactive">
                                            <span class="status-badge inactive">
                                                Inactive
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                @error('status')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end mt-3">
                        <div class="mb-3">

                            <button type="submit"
                                class="btn btn-primary"
                                style="width: 160px; height: 42px; border-radius: 6px; font-weight: 600;">
                                <i class="fas fa-save"></i> Submit
                            </button>


                            <button type="reset"
                                class="btn me-2"
                                style="width: 135px; height: 42px; border: none; border-radius: 6px; font-weight: 600; background: linear-gradient(135deg, #d96f00, #cc6900); color: #fff;">
                                <i class="fas fa-undo"></i> Reset
                            </button>

                            <a href="{{ route('organizations.index') }}"
                                class="btn btn-secondary me-2"
                                style="width: 130px; height: 42px; border-radius: 6px; font-weight: 600;">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>

                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- Crop Modal -->
<div class="crop-modal-overlay" id="cropModal">
    <div class="crop-modal-content">
        <div class="crop-modal-header">
            <h5><i class="fas fa-crop-alt me-2"></i> Crop Logo</h5>
            <button type="button" class="btn-close" id="cancelCropButton"></button>
        </div>
        <div class="crop-modal-body">
            <img id="crop-image" src="#" alt="Image to crop">
        </div>
        <div class="crop-modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelCropButton">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="btn btn-primary" id="cropButton">
                <i class="fas fa-crop-alt"></i> Crop & Upload
            </button>
        </div>
    </div>
</div>

@endsection