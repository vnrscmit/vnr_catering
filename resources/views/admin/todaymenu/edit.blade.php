@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
<link rel="stylesheet" href="/admin_resources/vendors/select2/select2.min.css">
<style>
    .submenu-checkbox:disabled+label {
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
<!-- Your existing scripts -->
<script>
    $(document).ready(function() {
        // Load menus when event changes
        $('#event_id').on('change', function() {
            var eventId = $(this).val();
            var locationId = {
                {
                    $locationId ?? 0
                }
            };

            if (eventId && locationId) {
                loadMenus(eventId, locationId);
            }
        });

        function loadMenus(eventId, locationId) {
            $('#menuTableContainer').html('<div class="text-center py-4"><i class="fa fa-spinner fa-spin"></i> Loading menus...</div>');

            $.ajax({
                url: "{{ route('admin.get-menus-by-event') }}",
                type: "GET",
                data: {
                    event_id: eventId,
                    location_id: locationId,
                    daily_menu_id: {
                        {
                            $dailyMenu - > id ?? 0
                        }
                    }
                },
                success: function(response) {
                    if (response.status) {
                        $('#menuTableContainer').html(response.html);
                        // Re-apply any existing selections
                        applySelectedSubmenus(response.selected_submenus);
                    } else {
                        $('#menuTableContainer').html('<div class="alert alert-info">' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#menuTableContainer').html('<div class="alert alert-danger">Error loading menus. Please try again.</div>');
                }
            });
        }

        function applySelectedSubmenus(selectedSubmenus) {
            if (selectedSubmenus) {
                $.each(selectedSubmenus, function(menuId, submenuIds) {
                    $.each(submenuIds, function(index, submenuId) {
                        $('#submenu_' + menuId + '_' + submenuId).prop('checked', true);
                    });
                });
            }
        }

        // Load initial menus if event is already selected
        var initialEventId = $('#event_id').val();
        var initialLocationId = {
            {
                $locationId ?? 0
            }
        };
        if (initialEventId && initialLocationId) {
            loadMenus(initialEventId, initialLocationId);
        }
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
                <h5 class="mb-0">Edit Today Menu - {{ auth()->user()->location->name ?? 'N/A' }}</h5>
            </div>
            <div class="card-body">

                <form action="{{ route('today-menu.update', $dailyMenu->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                      <input type="hidden" value="{{ $locationId }}" name="location_id">

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
                            <label><strong>Select Event <span class="text-danger">*</span></strong></label>
                            <select name="event_id" id="event_id"
                                class="form-control @error('event_id') is-invalid @enderror" readonly
                                required>
                                <option value="">-- Select Event --</option>
                                @foreach($eventList as $eventId => $eventName)
                                <option value="{{ $eventId }}"
                                    {{ old('event_id', $dailyMenu->event_id) == $eventId ? 'selected' : '' }}>
                                    {{ $eventName }}
                                </option>
                                @endforeach
                            </select>
                            @error('event_id')
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


            <!-- Menu Table Container -->
            <div id="menuTableContainer">
                @if(isset($menus) && $menus->isNotEmpty())
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
                                            @if($submenu->special_flag == 1)
                                            <i class="fa fa-star text-warning" title="Special"></i>
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
                @else
                <div class="text-center py-4">
                    <i class="fa fa-info-circle"></i> No menus found for this event
                </div>
                @endif
            </div>

            <div class="d-flex justify-content-end mt-2">
                <div class="mb-3">
                    <button type="submit"
                        class="btn me-2"
                        id="draftBtn"
                        name="action"
                        value="2"
                        style="width: 135px;
                                           height: 42px;
                                           border: none;
                                           border-radius: 6px;
                                           font-weight: 600;
                                           background: linear-gradient(135deg, #d96f00, #cc6900);
                                           color: #fff;">
                        <i class="fa fa-file"></i> Save Draft
                    </button>

                    <button type="submit"
                        class="btn btn-primary me-2"
                        name="action"
                        value="1"
                        style="width: 130px;
                                           height: 42px;
                                           border-radius: 6px;
                                           font-weight: 600;">
                        <i class="fa fa-file"></i> Publish
                    </button>

                    <a href="{{ route('today-menu.index') }}"
                        class="btn btn-secondary"
                        style="width: 130px;
                                      height: 42px;
                                      border-radius: 6px;
                                      font-weight: 600;">
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