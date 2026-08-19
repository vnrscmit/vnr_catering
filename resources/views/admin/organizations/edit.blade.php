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

    .current-logo {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 10px;
        display: inline-block;
        background: #f8f9fa;
    }

    .current-logo img {
        max-width: 100px;
        max-height: 100px;
        border-radius: 8px;
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
        // Crop variables
        let cropper = null;
        let currentFile = null;

   

        // State change - Load Districts
        $('#state').on('change', function() {
            var stateId = $(this).val();
            var districtSelect = $('#district');
            var tehsilSelect = $('#tehsil');
            
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
                            
                            // Set selected district if exists
                            @if(old('district') ?? $organization->district ?? '')
                                var oldDistrict = "{{ old('district', $organization->district ?? '') }}";
                                districtSelect.val(oldDistrict).trigger('change');
                            @endif
                        } else {
                            districtSelect.prop('disabled', true);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching districts:', error);
                        alert('Failed to load districts. Please try again.');
                    }
                });
            } else {
                districtSelect.prop('disabled', true);
            }
        });

        // District change - Load Tehsils
        $('#district').on('change', function() {
            var districtId = $(this).val();
            var tehsilSelect = $('#tehsil');
            
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
                            
                            // Set selected tehsil if exists
                            @if(old('tehsil') ?? $organization->tehsil ?? '')
                                var oldTehsil = "{{ old('tehsil', $organization->tehsil ?? '') }}";
                                tehsilSelect.val(oldTehsil).trigger('change');
                            @endif
                        } else {
                            tehsilSelect.prop('disabled', true);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching tehsils:', error);
                        alert('Failed to load tehsils. Please try again.');
                    }
                });
            } else {
                tehsilSelect.prop('disabled', true);
            }
        });

        // Trigger state change if there's a selected state
        @if(old('state') ?? $organization->state ?? '')
            var initialState = "{{ old('state', $organization->state ?? '') }}";
            $('#state').val(initialState).trigger('change');
        @endif

        // Logo upload with crop functionality
        $('#logo').change(function() {
            const file = this.files[0];
            if (file) {
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file.');
                    this.value = '';
                    return;
                }

                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB.');
                    this.value = '';
                    return;
                }

                currentFile = file;
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#crop-image').attr('src', e.target.result);
                    $('#cropModal').addClass('active');
                    
                    setTimeout(function() {
                        if (cropper) {
                            cropper.destroy();
                        }
                        cropper = new Cropper(document.getElementById('crop-image'), {
                            aspectRatio: 1,
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
                const canvas = cropper.getCroppedCanvas({
                    width: 300,
                    height: 300,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });

                if (canvas) {
                    canvas.toBlob(function(blob) {
                        const croppedFile = new File([blob], currentFile.name, {
                            type: currentFile.type,
                        });

                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(croppedFile);
                        document.getElementById('logo').files = dataTransfer.files;

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $('#logo-preview').attr('src', e.target.result).show();
                            $('#logo-placeholder').hide();
                            $('#logo-preview-container').show();
                            $('#current-logo-container').hide();
                        };
                        reader.readAsDataURL(croppedFile);

                        closeCropModal();
                        alert('Image cropped successfully!');
                    }, currentFile.type, 0.9);
                }
            }
        });

        // Cancel crop
        $('#cancelCropButton, .crop-modal-overlay').click(function(e) {
            if (e.target === this || $(this).is('#cancelCropButton')) {
                closeCropModal();
                $('#logo').val('');
            }
        });

        function closeCropModal() {
            $('#cropModal').removeClass('active');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }

        // Auto-uppercase GSTIN
        $('#gstin').on('input', function() {
            this.value = this.value.toUpperCase();
        });

        // Remove logo
        $('#removeLogoBtn').click(function() {
            if (confirm('Are you sure you want to remove the logo?')) {
                $.ajax({
                    url: "{{ route('organizations.removeLogo', $organization->id) }}",
                    type: "DELETE",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#current-logo-container').hide();
                            $('#logo-placeholder').show();
                            $('#logo-preview').hide();
                            alert('Logo removed successfully!');
                        }
                    },
                    error: function() {
                        alert('Failed to remove logo. Please try again.');
                    }
                });
            }
        });
    });
</script>
@endpush

@section('title', 'Edit Organization')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            @include('partials.message-bag')

            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i> Edit Organization
                    </h5>
                    <a href="{{ route('organizations.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="card-body">
                <form action="{{ route('organizations.update', $organization->id) }}" method="POST" enctype="multipart/form-data" id="organizationForm">
                    @csrf
                    @method('PUT')

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
                                    id="organization_name"
                                    name="organization_name"
                                    value="{{ old('organization_name', $organization->organization_name) }}"
                                    placeholder="Enter organization name"
                                    required>
                                @error('organization_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="short_name">
                                    Short Name <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('short_name') is-invalid @enderror"
                                    id="short_name"
                                    name="short_name"
                                    value="{{ old('short_name', $organization->short_name) }}"
                                    placeholder="e.g., ABC Corp"
                                    required>
                                <small class="text-muted-small">Short name or abbreviation for the organization</small>
                                @error('short_name')
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
                                    required>{{ old('address', $organization->address) }}</textarea>
                                @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Location Details Section -->
                    <div class="organization-card show">
                        <h6 class="section-header">
                            <i class="fas fa-map-marker-alt"></i> Location Details
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
                                        {{ old('state', $organization->state) == $state->id ? 'selected' : '' }}>
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
                                    value="{{ old('city_village', $organization->city_village) }}"
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
                                    value="{{ old('pincode', $organization->pincode) }}"
                                    placeholder="Enter pincode"
                                    maxlength="10"
                                    required>
                                <small class="text-muted-small">6-10 digit pincode</small>
                                @error('pincode')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                                    value="{{ old('max_location_allowed', $organization->max_location_allowed) }}"
                                    min="1"
                                    required>
                                <small class="text-muted-small">Maximum number of locations this organization can have</small>
                                @error('max_location_allowed')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="max_user_per_location">
                                    Max Users Per Location <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                    class="form-control @error('max_user_per_location') is-invalid @enderror"
                                    id="max_user_per_location"
                                    name="max_user_per_location"
                                    value="{{ old('max_user_per_location', $organization->max_user_per_location) }}"
                                    min="1"
                                    required>
                                <small class="text-muted-small">Maximum number of users allowed per location</small>
                                @error('max_user_per_location')
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
                                    value="{{ old('gstin', $organization->gstin) }}"
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
                                <!-- Current Logo -->
                                @if($organization->logo)
                                <div id="current-logo-container" class="mb-3">
                                    <label class="form-label">Current Logo:</label>
                                    <div class="current-logo">
                                        <img src="{{ asset('storage/organizations/logos/' . $organization->logo) }}" 
                                             alt="Current Logo">
                                    </div>
                                    <button type="button" id="removeLogoBtn" class="btn btn-danger btn-sm mt-2">
                                        <i class="fas fa-trash"></i> Remove Logo
                                    </button>
                                </div>
                                @endif

                                <!-- Preview Container -->
                                <div class="preview-container" id="logo-preview-container" style="display: none;">
                                    <img id="logo-preview" src="#" alt="Logo Preview">
                                </div>
                                <div class="preview-container" id="logo-placeholder" @if($organization->logo) style="display: none;" @endif>
                                    <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                    <p class="placeholder-text">No logo uploaded yet</p>
                                </div>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex align-items-center gap-4">
                                    <div class="form-check me-4">
                                        <input class="form-check-input" type="radio"
                                            name="status" id="status_active" value="1"
                                            {{ old('status', $organization->status) == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="status_active">
                                            <span class="status-badge active">
                                                Active
                                            </span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio"
                                            name="status" id="status_inactive" value="0"
                                            {{ old('status', $organization->status) == '0' ? 'checked' : '' }}>
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
                                <i class="fas fa-save"></i> Update
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