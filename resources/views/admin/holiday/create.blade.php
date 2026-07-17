@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .weekly-checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
        border: 1px solid #dee2e6;
    }

    .weekly-checkbox-group .form-check {
        padding-left: 25px;
    }

    .weekly-checkbox-group .form-check-input {
        margin-left: -25px;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .weekly-checkbox-group .form-check-label {
        cursor: pointer;
        font-weight: 500;
    }

    .holiday-type-selector {
        margin-bottom: 20px;
    }

    .holiday-type-selector .btn-group {
        width: 100%;
    }

    .holiday-type-selector .btn {
        flex: 1;
        padding: 10px;
        font-weight: 500;
    }

    .date-list-container {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 8px;
        background: #fff;
        min-height: 50px;
    }

    .date-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 12px;
        margin-bottom: 4px;
        background: #f8f9fa;
        border-radius: 4px;
        border-left: 3px solid #007bff;
    }

    .date-item:hover {
        background: #e9ecef;
    }

    .date-item .date-text {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .date-item .date-text .day-badge {
        background: #007bff;
        color: white;
        padding: 1px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        min-width: 40px;
        text-align: center;
    }

    .date-item .remove-date {
        color: #dc3545;
        cursor: pointer;
        border: none;
        background: none;
        font-size: 16px;
        padding: 0 5px;
        transition: all 0.2s ease;
    }

    .date-item .remove-date:hover {
        color: #bd2130;
        transform: scale(1.1);
    }

    .selected-dates-badge {
        display: inline-block;
        background: #007bff;
        color: white;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 12px;
        margin-left: 10px;
    }

    .date-input-group {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .date-input-group .form-control {
        flex: 1;
    }

    .btn-add-date {
        min-width: 80px;
    }

    .empty-dates-message {
        text-align: center;
        color: #6c757d;
        padding: 15px;
        font-style: italic;
        font-size: 14px;
    }

    .quick-add-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
    }

    .quick-add-buttons .btn {
        font-size: 12px;
        padding: 3px 10px;
    }

    .section-card {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin-bottom: 15px;
    }

    .section-card .card-header {
        background: #f8f9fa;
        padding: 10px 15px;
        border-bottom: 1px solid #dee2e6;
        font-weight: 600;
        color: #495057;
    }

    .section-card .card-body {
        padding: 15px;
    }

    .counter-badge {
        background: #28a745;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }

    .btn-primary:hover {
        background-color: #0069d9;
        border-color: #0062cc;
    }

    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
    }

    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }

    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .btn-danger:hover {
        background-color: #c82333;
        border-color: #bd2130;
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

<script>
    $(document).ready(function() {
        // Holiday type selection - SHOW/HIDE sections
        $('input[name="holiday_type"]').change(function() {
            var type = $(this).val();
            $('#holidayDatesSection').hide();
            $('#weeklyOffSection').hide();
            $('#specificDateSection').hide();

            if (type === 'holiday') {
                $('#holidayDatesSection').show();
            } else if (type === 'weekly_off') {
                $('#weeklyOffSection').show();
            } else if (type === 'specific_date') {
                $('#specificDateSection').show();
            }
        });

        // Add date for holiday dates
        $('#addHolidayDate').click(function(e) {
            e.preventDefault();
            var dateInput = $('#holidayDateInput');
            var dateValue = dateInput.val();

            if (!dateValue) {
                alert('Please select a date first');
                return;
            }

            addHolidayDate(dateValue);
            dateInput.val('');
        });

        // Enter key support for adding holiday dates
        $('#holidayDateInput').keypress(function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#addHolidayDate').click();
            }
        });

        // Add date for specific dates
        $('#addDate').click(function(e) {
            e.preventDefault();
            var dateInput = $('#specificDateInput');
            var dateValue = dateInput.val();

            if (!dateValue) {
                alert('Please select a date first');
                return;
            }

            addSpecificDate(dateValue);
            dateInput.val('');
        });

        // Enter key support for adding specific dates
        $('#specificDateInput').keypress(function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#addDate').click();
            }
        });

        // Clear all dates button
        $('#clearAllHolidays').click(function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to clear all holiday dates?')) {
                $('#selectedHolidayDatesList').empty();
                updateHolidayDateCount();
                checkEmptyList('holiday');
            }
        });

        $('#clearAllSpecificDates').click(function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to clear all specific dates?')) {
                $('#selectedDatesList').empty();
                updateDateCount();
                checkEmptyList('specific');
            }
        });

        // Quick add buttons for common holidays
        $('#quickAddNewYear').click(function(e) {
            e.preventDefault();
            var year = new Date().getFullYear();
            addHolidayDate(year + '-01-01');
        });

        $('#quickAddRepublic').click(function(e) {
            e.preventDefault();
            var year = new Date().getFullYear();
            addHolidayDate(year + '-01-26');
        });

        $('#quickAddIndependence').click(function(e) {
            e.preventDefault();
            var year = new Date().getFullYear();
            addHolidayDate(year + '-08-15');
        });

        $('#quickAddChristmas').click(function(e) {
            e.preventDefault();
            var year = new Date().getFullYear();
            addHolidayDate(year + '-12-25');
        });

        // Select/Deselect All for Weekly Off
        $('#selectAllDays').click(function(e) {
            e.preventDefault();
            selectAllDays(true);
        });

        $('#deselectAllDays').click(function(e) {
            e.preventDefault();
            selectAllDays(false);
        });

        // FORM SUBMIT VALIDATION
        $('form').on('submit', function(e) {
            var holidayType = $('input[name="holiday_type"]:checked').val();
            var isValid = true;
            var errorMessage = '';

            if (!holidayType) {
                e.preventDefault();
                alert('Please select a holiday type');
                return false;
            }

            if (holidayType === 'specific_date') {
                var dates = $('#selectedDatesList .date-item').length;
                if (dates === 0) {
                    e.preventDefault();
                    alert('Please add at least one specific date');
                    return false;
                }
            } else if (holidayType === 'holiday') {
                var holidayDates = $('#selectedHolidayDatesList .date-item').length;
                if (holidayDates === 0) {
                    e.preventDefault();
                    alert('Please add at least one holiday date');
                    return false;
                }
            } else if (holidayType === 'weekly_off') {
                var selectedDays = getSelectedDays();
                if (selectedDays.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one weekly off day');
                    return false;
                }
            }

            // If all validation passes, form will submit
            return true;
        });
    });

    // ============ FUNCTIONS ============

    function addHolidayDate(dateValue) {
        // Check if date already exists
        var exists = false;
        $('#selectedHolidayDatesList .date-item').each(function() {
            if ($(this).data('date') === dateValue) {
                exists = true;
                return false;
            }
        });

        if (exists) {
            alert('This date is already added to the holiday list');
            return;
        }

        // Format date to d-m-Y
        var formattedDate = formatDateDMY(dateValue);
        var dayName = getDayOfWeek(dateValue);

        // Add date to list
        var dateHtml = `
        <div class="date-item" data-date="${dateValue}">
            <div class="date-text">
                <span class="day-badge">${dayName}</span>
                <span>${formattedDate}</span>
            </div>
            <button type="button" class="remove-date" onclick="removeHolidayDate(this)" title="Remove date">
                <i class="fas fa-minus-circle"></i>
            </button>
            <input type="hidden" name="holiday_dates[]" value="${dateValue}">
        </div>
    `;

        $('#selectedHolidayDatesList').append(dateHtml);
        updateHolidayDateCount();
        removeEmptyMessage('holiday');
    }

    function addSpecificDate(dateValue) {
        // Check if date already exists
        var exists = false;
        $('#selectedDatesList .date-item').each(function() {
            if ($(this).data('date') === dateValue) {
                exists = true;
                return false;
            }
        });

        if (exists) {
            alert('This date is already added to the specific dates list');
            return;
        }

        // Format date to d-m-Y
        var formattedDate = formatDateDMY(dateValue);
        var dayName = getDayOfWeek(dateValue);

        // Add date to list
        var dateHtml = `
        <div class="date-item" data-date="${dateValue}">
            <div class="date-text">
                <span class="day-badge">${dayName}</span>
                <span>${formattedDate}</span>
            </div>
            <button type="button" class="remove-date" onclick="removeSpecificDate(this)" title="Remove date">
                <i class="fas fa-minus-circle"></i>
            </button>
            <input type="hidden" name="specific_dates[]" value="${dateValue}">
        </div>
    `;

        $('#selectedDatesList').append(dateHtml);
        updateDateCount();
        removeEmptyMessage('specific');
    }

    function removeHolidayDate(element) {
        $(element).closest('.date-item').fadeOut(200, function() {
            $(this).remove();
            updateHolidayDateCount();
            checkEmptyList('holiday');
        });
    }

    function removeSpecificDate(element) {
        $(element).closest('.date-item').fadeOut(200, function() {
            $(this).remove();
            updateDateCount();
            checkEmptyList('specific');
        });
    }

    function formatDateDMY(dateString) {
        var date = new Date(dateString);
        var day = String(date.getDate()).padStart(2, '0');
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var year = date.getFullYear();
        return day + '-' + month + '-' + year;
    }

    function getDayOfWeek(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            weekday: 'short'
        });
    }

    function updateHolidayDateCount() {
        var count = $('#selectedHolidayDatesList .date-item').length;
        $('#holidayDateCount').text(count + ' dates');
        if (count > 0) {
            $('#holidayDateCount').addClass('counter-badge');
        } else {
            $('#holidayDateCount').removeClass('counter-badge');
        }
    }

    function updateDateCount() {
        var count = $('#selectedDatesList .date-item').length;
        $('#dateCount').text(count + ' dates');
        if (count > 0) {
            $('#dateCount').addClass('counter-badge');
        } else {
            $('#dateCount').removeClass('counter-badge');
        }
    }

    function getSelectedDays() {
        var days = [];
        $('.weekly-checkbox-group input[type="checkbox"]:checked').each(function() {
            days.push($(this).val());
        });
        return days;
    }

    function removeEmptyMessage(type) {
        var container = type === 'holiday' ? '#selectedHolidayDatesList' : '#selectedDatesList';
        $(container + ' .empty-dates-message').remove();
    }

    function checkEmptyList(type) {
        var container = type === 'holiday' ? '#selectedHolidayDatesList' : '#selectedDatesList';
        var count = $(container + ' .date-item').length;
        if (count === 0) {
            var message = `
            <div class="empty-dates-message">
                <i class="fas fa-calendar-plus"></i> No dates added yet
            </div>
        `;
            $(container).append(message);
        }
    }

    function selectAllDays(select) {
        $('.weekly-checkbox-group input[type="checkbox"]').prop('checked', select);
        updateWeeklyCount();
    }

    function updateWeeklyCount() {
        var count = $('.weekly-checkbox-group input[type="checkbox"]:checked').length;
        $('#weeklyCount').text(count + ' days');
    }

    // Get current date for min attribute
    function getTodayDate() {
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var yyyy = today.getFullYear();
        return yyyy + '-' + mm + '-' + dd;
    }

    // Set min date for date inputs
    $(document).ready(function() {
        var today = getTodayDate();
        $('#specificDateInput, #holidayDateInput').attr('min', today);

        // Add empty messages
        checkEmptyList('holiday');
        checkEmptyList('specific');
    });
</script>
@endpush

@section('title', 'Create Holiday Setting')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        @include('partials.message-bag')
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calendar-alt"></i> Create Holiday Setting</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('holiday-settings.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Location <span class="text-danger">*</span></label>
                            <select name="location_id" class="form-control" required>
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('location_id')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label>Select Holiday Type <span class="text-danger">*</span></label>
                            <div class="holiday-type-selector">
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="holiday_type" id="holidayType1" value="holiday" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="holidayType1">
                                        <i class="fas fa-calendar-day"></i> Holiday
                                    </label>

                                    <input type="radio" class="btn-check" name="holiday_type" id="holidayType2" value="weekly_off" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="holidayType2">
                                        <i class="fas fa-calendar-week"></i> Weekly Off
                                    </label>

                                    <input type="radio" class="btn-check" name="holiday_type" id="holidayType3" value="specific_date" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="holidayType3">
                                        <i class="fas fa-calendar-alt"></i> Specific Dates
                                    </label>
                                </div>
                            </div>
                            @error('holiday_type')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Holiday Dates Section -->
                    <div id="holidayDatesSection" style="display: none;">
                        <div class="section-card">
                            <div class="card-header">
                                <i class="fas fa-list"></i> Holiday Dates
                                <span id="holidayDateCount" class="selected-dates-badge">0 dates</span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="date-input-group">
                                            <input type="date"
                                                id="holidayDateInput"
                                                class="form-control"
                                                min="{{ date('Y-m-d') }}">
                                            <button type="button" id="addHolidayDate" class="btn btn-primary btn-add-date">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </div>
                                        <small class="text-muted">Press Enter to add</small>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="quick-add-buttons">
                                            <button type="button" class="btn btn-sm btn-success" id="quickAddNewYear">New Year</button>
                                            <button type="button" class="btn btn-sm btn-success" id="quickAddRepublic">Republic</button>
                                            <button type="button" class="btn btn-sm btn-success" id="quickAddIndependence">Independence</button>
                                            <button type="button" class="btn btn-sm btn-success" id="quickAddChristmas">Christmas</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="font-weight-bold">Selected Dates</label>
                                        <button type="button" class="btn btn-sm btn-danger" id="clearAllHolidays">
                                            <i class="fas fa-trash"></i> Clear All
                                        </button>
                                    </div>
                                    <div id="selectedHolidayDatesList" class="date-list-container">
                                        @if(old('holiday_dates'))
                                        @foreach(old('holiday_dates') as $date)
                                        <div class="date-item" data-date="{{ $date }}">
                                            <div class="date-text">
                                                <span class="day-badge">{{ date('D', strtotime($date)) }}</span>
                                                <span>{{ date('d-m-Y', strtotime($date)) }}</span>
                                            </div>
                                            <button type="button" class="remove-date" onclick="removeHolidayDate(this)">
                                                <i class="fas fa-minus-circle"></i>
                                            </button>
                                            <input type="hidden" name="holiday_dates[]" value="{{ $date }}">
                                        </div>
                                        @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Weekly Off Section -->
                    <div id="weeklyOffSection" style="display: none;">
                        <div class="section-card">
                            <div class="card-header">
                                <i class="fas fa-calendar-week"></i> Weekly Off Days
                                <span class="selected-dates-badge" id="weeklyCount">0 days</span>
                            </div>
                            <div class="card-body">
                                <div class="weekly-checkbox-group">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="weekly_days[]" value="Monday" id="monday" onchange="updateWeeklyCount()">
                                        <label class="form-check-label" for="monday">Monday</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="weekly_days[]" value="Tuesday" id="tuesday" onchange="updateWeeklyCount()">
                                        <label class="form-check-label" for="tuesday">Tuesday</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="weekly_days[]" value="Wednesday" id="wednesday" onchange="updateWeeklyCount()">
                                        <label class="form-check-label" for="wednesday">Wednesday</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="weekly_days[]" value="Thursday" id="thursday" onchange="updateWeeklyCount()">
                                        <label class="form-check-label" for="thursday">Thursday</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="weekly_days[]" value="Friday" id="friday" onchange="updateWeeklyCount()">
                                        <label class="form-check-label" for="friday">Friday</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="weekly_days[]" value="Saturday" id="saturday" onchange="updateWeeklyCount()">
                                        <label class="form-check-label" for="saturday">Saturday</label>
                                    </div>
                                        <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="weekly_days[]" value="Sunday" id="sunday" onchange="updateWeeklyCount()">
                                        <label class="form-check-label" for="sunday">Sunday</label>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-sm btn-secondary" id="selectAllDays">
                                        <i class="fas fa-check-double"></i> Select All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" id="deselectAllDays">
                                        <i class="fas fa-times"></i> Deselect All
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Specific Dates Section -->
                    <div id="specificDateSection" style="display: none;">
                        <div class="section-card">
                            <div class="card-header">
                                <i class="fas fa-calendar-check"></i> Specific Dates
                                <span id="dateCount" class="selected-dates-badge">0 dates</span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="date-input-group">
                                            <input type="date"
                                                id="specificDateInput"
                                                class="form-control"
                                                min="{{ date('Y-m-d') }}">
                                            <button type="button" id="addDate" class="btn btn-primary btn-add-date">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </div>
                                        <small class="text-muted">Press Enter to add</small>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-sm btn-danger w-100" id="clearAllSpecificDates">
                                            <i class="fas fa-trash"></i> Clear
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="font-weight-bold">Selected Dates</label>
                                    <div id="selectedDatesList" class="date-list-container">
                                        @if(old('specific_dates'))
                                        @foreach(old('specific_dates') as $date)
                                        <div class="date-item" data-date="{{ $date }}">
                                            <div class="date-text">
                                                <span class="day-badge">{{ date('D', strtotime($date)) }}</span>
                                                <span>{{ date('d-m-Y', strtotime($date)) }}</span>
                                            </div>
                                            <button type="button" class="remove-date" onclick="removeSpecificDate(this)">
                                                <i class="fas fa-minus-circle"></i>
                                            </button>
                                            <input type="hidden" name="specific_dates[]" value="{{ $date }}">
                                        </div>
                                        @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end mt-3">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Submit
                            </button>
                            <a href="{{ route('holiday-settings.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection