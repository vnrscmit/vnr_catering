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
    $(function() {
        $('.select2').select2({
            placeholder: 'Select Menu Items',
            allowClear: true
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

        {{-- Link Locations --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Add Today Menu</h5>
            </div>
            <div class="card-body">

                <form action="{{ route('today-menu.store') }}" method="POST">
                    @csrf

                    <div class="row mb-3">

                        <div class="col-md-4">
                            <label><strong>Select Date <span class="text-danger">*</span></strong></label>

                            <input
                                type="date"
                                name="menu_date"
                                class="form-control @error('menu_date') is-invalid @enderror"
                                value="{{ old('menu_date') }}"
                                min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                                max="{{ \Carbon\Carbon::today()->addDays(6)->format('Y-m-d') }}"
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
                                <option value="{{ $location->id }}" selected
                                    {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                                @endforeach

                            </select>

                            @error('location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Guest Allowed -->
                        <div class="col-md-4">
                            <label><strong>Feast Day<span class="text-danger"> *</span></strong></label>
                            <div class="d-flex align-items-center gap-4">
                                <div class="form-check me-4">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="special_flag"
                                        id="special_flag_no"
                                        value="0"
                                        {{ old('special_flag', 0) == 0 ? 'checked' : '' }}>
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
                                        {{ old('special_flag') == 1 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="special_flag_yes">
                                        Yes
                                    </label>
                                </div>
                            </div>
                        </div>




                    </div>

                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="30%">Menu Section</th>
                                <th width="70%">Menu Items</th>
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
                                    <div class="row">
                                        @foreach($menu->submenus as $submenu)
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input
                                                    type="checkbox"
                                                    name="submenu_id[{{ $menu->id }}][]"
                                                    value="{{ $submenu->id }}"
                                                    id="submenu_{{ $menu->id }}_{{ $submenu->id }}"
                                                    class="form-check-input submenu-checkbox"
                                                    data-menu-id="{{ $menu->id }}"
                                                    data-submenu-id="{{ $submenu->id }}"
                                                    {{ (old('submenu_id.'.$menu->id) && in_array($submenu->id, old('submenu_id.'.$menu->id))) ? 'checked' : '' }}>
                                                <label
                                                    class="form-check-label"
                                                    for="submenu_{{ $menu->id }}_{{ $submenu->id }}">
                                                    {{ $submenu->name }}

                                                    {{-- Show special indicator if submenu is special --}}
                                                    @if($submenu->special_flag == 1)
                                                    <i class="fa fa-star text-warning" title="Special"></i>
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>


                    <div class="d-flex justify-content-end  mt-2">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                Submit
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