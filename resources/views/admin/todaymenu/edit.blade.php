@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
<link rel="stylesheet" href="/admin_resources/vendors/select2/select2.min.css">
<style>
    .submenu-checkbox:disabled + label {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .submenu-checkbox:disabled {
        cursor: not-allowed;
    }
    .special-warning {
        font-size: 11px;
        color: #dc3545;
        display: block;
    }
    .feast-badge {
        font-size: 10px;
        padding: 2px 6px;
        margin-left: 4px;
    }
    .checkbox-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 5px;
    }
    @media (max-width: 768px) {
        .checkbox-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 576px) {
        .checkbox-grid {
            grid-template-columns: 1fr;
        }
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
<script src="/admin_resources/vendors/select2/select2.min.js"></script>
<script>
    $(function() {
        // Initialize select2 if still needed elsewhere
        $('.select2').select2({
            placeholder: 'Select SubMenu',
            allowClear: true
        });

        // Handle Feast Day toggle for checkboxes
       

        // Trigger on page load with a small delay to ensure DOM is ready
        setTimeout(handleFeastDayToggle, 100);

        // Trigger on radio button change
        $('input[name="special_flag"]').on('change', function() {
            handleFeastDayToggle();
        });

        // Also trigger when date changes (if needed for date-based feast days)
        $('input[name="menu_date"]').on('change', function() {
            // Add logic here if feast days are determined by date
        });
    });
</script>
@endpush

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Today Menu</h5>
            </div>
            <div class="card-body">

                <form action="{{ route('today-menu.update', $dailyMenu->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Date Selection -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label><strong>Select Date <span class="text-danger">*</span></strong></label>
                            <input
                                type="date"
                                name="menu_date"
                                class="form-control @error('menu_date') is-invalid @enderror"
                                min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                                value="{{ old('menu_date', $dailyMenu->menu_date) }}"
                                required>
                            @error('menu_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label><strong>Select Location <span class="text-danger">*</span></strong></label>
                            <select name="location_id"
                                class="form-control @error('location_id') is-invalid @enderror"
                                required>
                                <option value="">-- Select Location --</option>
                                @foreach($locations as $location)
                                <option value="{{ $location->id }}"
                                    {{ old('location_id', $dailyMenu->location_id) == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Special -->
                        <div class="col-md-4">
                            <label><strong>Feast Day <span class="text-danger">*</span></strong></label>
                            <div class="d-flex align-items-center gap-4">
                                <div class="form-check me-4">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="special_flag"
                                        id="special_flag_no"
                                        value="0"
                                        {{ old('special_flag', $dailyMenu->special_flag) == 0 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="special_flag_no">
                                        No
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="special_flag"
                                        id="special_flag_yes"
                                        value="1"
                                        {{ old('special_flag', $dailyMenu->special_flag) == 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="special_flag_yes">
                                        Yes
                                    </label>
                                </div>
                            </div>
                            @error('special_flag')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="30%">Menu</th>
                                <th width="70%">Sub Menu</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($menus as $menu)
                            <tr>
                                <td>
                                    <strong>{{ $menu->name }}</strong>
                                    <input type="hidden" name="menu_id[]" value="{{ $menu->id }}">
                                </td>

                                <td>
                                    <div class="checkbox-grid">
                                        @foreach($menu->subMenus as $submenu)
                                        <div class="form-check">
                                            <input
                                                type="checkbox"
                                                name="submenu_id[{{ $menu->id }}][]"
                                                value="{{ $submenu->id }}"
                                                id="submenu_{{ $menu->id }}_{{ $submenu->id }}"
                                                class="form-check-input submenu-checkbox"
                                                data-menu-id="{{ $menu->id }}"
                                                data-submenu-id="{{ $submenu->id }}"
                                                data-special="{{ $submenu->special_flag }}"
                                                {{ (old('submenu_id.'.$menu->id) !== null) 
                                                    ? (in_array($submenu->id, old('submenu_id.'.$menu->id)) ? 'checked' : '') 
                                                    : (in_array($submenu->id, $selectedSubmenus[$menu->id] ?? []) ? 'checked' : '') }}>
                                            <label
                                                class="form-check-label"
                                                for="submenu_{{ $menu->id }}_{{ $submenu->id }}">
                                                {{ $submenu->name }}

                                                {{-- Show special indicator if submenu is special --}}
                                                @if($submenu->special_flag == 1)
                                                <span class="badge bg-warning text-dark feast-badge">
                                                    <i class="fa fa-star"></i> Feast
                                                </span>
                                                @endif
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-end mt-2">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Update
                            </button>
                            <a href="{{ route('today-menu.index') }}" class="btn btn-secondary">
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