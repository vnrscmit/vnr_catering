@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Main color scheme */
    :root {
        --primary-green: #2E6115;
        --primary-green-light: #3d7a1e;
        --primary-green-dark: #1f4210;
        --primary-green-bg: #f0f7ea;
        --primary-green-border: #c8dfb8;
        --primary-green-hover: #3d7a1e;
        --primary-green-soft: #e8f2e0;
    }

    .weekly-checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
        padding: 15px;
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid #e8ecef;
    }

    .weekly-checkbox-group .form-check {
        padding-left: 28px;
    }

    .weekly-checkbox-group .form-check-input {
        margin-left: -28px;
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: var(--primary-green);
    }

    .weekly-checkbox-group .form-check-input:checked {
        background-color: var(--primary-green);
        border-color: var(--primary-green);
    }

    .weekly-checkbox-group .form-check-label {
        cursor: pointer;
        font-weight: 500;
        color: #2d3748;
    }

    .holiday-type-selector {
        margin-bottom: 20px;
    }

    .holiday-type-selector .btn-group {
        width: 100%;
    }

    .holiday-type-selector .btn {
        flex: 1;
        padding: 12px 10px;
        font-weight: 500;
        border-radius: 0;
        border: 2px solid #e8ecef;
        background: #ffffff;
        color: #4a5568;
        transition: all 0.3s ease;
    }

    .holiday-type-selector .btn:first-child {
        border-radius: 8px 0 0 8px;
    }

    .holiday-type-selector .btn:last-child {
        border-radius: 0 8px 8px 0;
    }

    .holiday-type-selector .btn:hover {
        background: var(--primary-green-bg);
        border-color: var(--primary-green);
        color: var(--primary-green);
    }

    .holiday-type-selector .btn-check:checked+.btn {
        background: var(--primary-green);
        border-color: var(--primary-green);
        color: #ffffff;
    }

    .date-list-container {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #e8ecef;
        border-radius: 8px;
        padding: 8px;
        background: #ffffff;
        min-height: 50px;
    }

    .date-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 14px;
        margin-bottom: 4px;
        background: #f8fafc;
        border-radius: 6px;
        border-left: 4px solid var(--primary-green);
        transition: all 0.2s ease;
    }

    .date-item:hover {
        background: var(--primary-green-bg);
        transform: translateX(2px);
    }

    .date-item .date-text {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
    }

    .date-item .date-text .day-badge {
        background: var(--primary-green);
        color: white;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        min-width: 44px;
        text-align: center;
    }

    .date-item .date-remark {
        flex: 1;
        margin: 0 10px;
        font-size: 13px;
        color: #4a5568;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .date-item .remove-date {
        color: #e53e3e;
        cursor: pointer;
        border: none;
        background: none;
        font-size: 16px;
        padding: 0 8px;
        transition: all 0.2s ease;
        opacity: 0.6;
    }

    .date-item .remove-date:hover {
        color: #c53030;
        transform: scale(1.2);
        opacity: 1;
    }

    .selected-dates-badge {
        display: inline-block;
        color: white;
        padding: 3px 14px;
        border-radius: 20px;
        font-size: 12px;
        margin-left: 10px;
        background: var(--primary-green);
        font-weight: 500;
    }

    .date-input-group {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .date-input-group .form-control {
        flex: 1;
        min-width: 150px;
        border: 2px solid #e8ecef;
        border-radius: 8px;
        padding: 10px 14px;
        transition: all 0.3s ease;
    }

    .date-input-group .form-control:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(46, 97, 21, 0.15);
    }

    .date-input-group .form-control.date-input {
        flex: 0 0 180px;
    }

    .date-input-group .form-control.remark-input {
        flex: 2;
        min-width: 200px;
    }

    .btn-add-date {
        min-width: 80px;
        white-space: nowrap;
        background: var(--primary-green);
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-add-date:hover {
        background: var(--primary-green-light);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(46, 97, 21, 0.3);
    }

    .empty-dates-message {
        text-align: center;
        color: #a0aec0;
        padding: 20px;
        font-style: italic;
        font-size: 14px;
    }

    .empty-dates-message i {
        color: var(--primary-green);
        margin-right: 8px;
        opacity: 0.5;
    }

    .section-card {
        border: 1px solid #e8ecef;
        border-radius: 10px;
        margin-bottom: 20px;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }

    .section-card .card-header {
        background: #fafcf8;
        padding: 14px 20px;
        border-bottom: 1px solid #e8ecef;
        font-weight: 600;
        color: #2d3748;
        display: flex;
        align-items: center;
    }

    .section-card .card-header i {
        color: var(--primary-green);
        margin-right: 10px;
        font-size: 16px;
    }

    .section-card .card-body {
        padding: 20px;
    }

    .counter-badge {
        background: var(--primary-green);
        color: white;
        padding: 2px 12px;
        border-radius: 20px;
        font-size: 12px;
    }

    .btn-danger {
        background-color: #fc8181;
        border: none;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .btn-danger:hover {
        background-color: #f56565;
    }

    .input-group-label {
        font-weight: 500;
        font-size: 14px;
        color: #4a5568;
        min-width: 50px;
    }

    /* Card styling */
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        background: #ffffff;
    }

    .card-header {
        background: #fafcf8 !important;
        border-bottom: 2px solid var(--primary-green) !important;
        border-radius: 12px 12px 0 0 !important;
        padding: 18px 24px;
    }

    .card-header h5 {
        color: var(--primary-green);
        font-weight: 600;
    }

    .card-header h5 i {
        margin-right: 10px;
    }

    .card-body {
        padding: 24px;
    }

    /* Form labels */
    label {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 6px;
        font-size: 14px;
    }

    .text-danger {
        color: #e53e3e !important;
    }

    /* Select and input styling */
    .form-control,
    .form-select {
        border: 2px solid #e8ecef;
        border-radius: 8px;
        padding: 10px 14px;
        transition: all 0.3s ease;
        background: #ffffff;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(46, 97, 21, 0.15);
    }

    /* Holiday buttons */
    .holiday-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .holiday-btn {
        flex: 1 1 180px;
        max-width: 180px;
        min-width: 180px;
        min-height: 100px;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 10px;
        background: #fff;
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        transition: all .2s ease;
    }

    .holiday-btn:hover {
        background: var(--primary-green-bg);
        border-color: var(--primary-green);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(46, 97, 21, 0.15);
        color: var(--primary-green);
    }

    .holiday-btn strong {
        display: block;
        font-size: 15px;
        color: #2d3748;
    }

    .holiday-btn small {
        font-size: 11px;
        color: #718096;
        font-weight: normal;
    }

    .holiday-btn:hover small {
        color: var(--primary-green);
    }

    /* Main panel */
    .main-panel {
        background: #f7fafc;
    }

    .content-wrapper {
        background: #f7fafc;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .holiday-type-selector .btn {
            font-size: 12px;
            padding: 10px 6px;
        }

        .date-input-group {
            flex-direction: column;
            align-items: stretch;
        }

        .date-input-group .form-control.date-input,
        .date-input-group .form-control.remark-input {
            flex: 1;
            min-width: 100%;
        }

        .btn-add-date {
            width: 100%;
        }

        .holiday-buttons {
            gap: 8px;
        }

        .holiday-btn {
            min-width: 70px;
            padding: 8px 12px;
            font-size: 12px;
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            var remarkInput = $('#holidayRemarkInput');
            var dateValue = dateInput.val();
            var remarkValue = remarkInput.val().trim();

            if (!dateValue) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Date Required',
                    text: 'Please select a date first.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            addHolidayDate(dateValue, remarkValue);
            dateInput.val('');
            remarkInput.val('');
        });

        // Enter key support for adding holiday dates
        $('#holidayDateInput, #holidayRemarkInput').keypress(function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#addHolidayDate').click();
            }
        });

        // Add date for Specific Off Dates
        $('#addDate').click(function(e) {
            e.preventDefault();
            var dateInput = $('#specificDateInput');
            var remarkInput = $('#specificRemarkInput');
            var dateValue = dateInput.val();
            var remarkValue = remarkInput.val().trim();

            if (!dateValue) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Date Required',
                    text: 'Please select a date first.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            addSpecificDate(dateValue, remarkValue);
            dateInput.val('');
            remarkInput.val('');
        });

        // Enter key support for adding Specific Off Dates
        $('#specificDateInput, #specificRemarkInput').keypress(function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#addDate').click();
            }
        });

        // Quick holiday buttons
        $('.holiday-btn').click(function() {
            $('#holidayDateInput').val($(this).data('date'));
            $('#holidayRemarkInput').val($(this).data('remark'));
            $('#addHolidayDate').trigger('click');
        });

        // FORM SUBMIT VALIDATION
        $('form').on('submit', function(e) {
            var holidayType = $('input[name="holiday_type"]:checked').val();
            var isValid = true;
            var errorMessage = '';

            if (!holidayType) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Date Required',
                    text: 'Please select a holiday type.',
                    confirmButtonText: 'OK'
                });
                return false;
            }

            if (holidayType === 'specific_date') {
                var dates = $('#selectedDatesList .date-item').length;
                if (dates === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Date Required',
                        text: 'Please add at least one specific date.',
                        confirmButtonText: 'OK'
                    });
                    return false;
                }
            } else if (holidayType === 'holiday') {
                var holidayDates = $('#selectedHolidayDatesList .date-item').length;
                if (holidayDates === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Date Required',
                        text: 'Please add at least one holiday date.',
                        confirmButtonText: 'OK'
                    });
                    return false;
                }
            } else if (holidayType === 'weekly_off') {
                var selectedDays = getSelectedDays();
                if (selectedDays.length === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Date Required',
                        text: 'Please select at least one weekly off day.',
                        confirmButtonText: 'OK'
                    });
                    return false;
                }
            }

            return true;
        });

        // Initialize empty messages
        checkEmptyList('holiday');
        checkEmptyList('specific');

        // Set min date for date inputs
        var today = getTodayDate();
        $('#specificDateInput, #holidayDateInput').attr('min', today);
    });

    // ============ HOLIDAY DATES FUNCTIONS ============
    
    function addHolidayDate(dateValue, remarkValue) {
        // Check if date already exists
        var exists = false;
        $('#selectedHolidayDatesList .date-item').each(function() {
            if ($(this).data('date') === dateValue) {
                exists = true;
                return false;
            }
        });

        if (exists) {
            Swal.fire({
                icon: 'warning',
                title: 'Date Required',
                text: 'This date is already added to the holiday list.',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Format date to d-m-Y
        var formattedDate = formatDateDMY(dateValue);
        var dayName = getDayOfWeek(dateValue);
        var remarkText = remarkValue || '';

        // Create date item HTML
        var dateHtml = `
            <div class="date-item" data-date="${dateValue}">
                <div class="date-text">
                    <span class="day-badge">${dayName}</span>
                    <span>${formattedDate}</span>
                    <span class="date-remark">${remarkText}</span>
                </div>
                <button type="button" class="remove-date" onclick="removeHolidayDate(this)" title="Remove date">
                    <i class="fas fa-minus-circle"></i>
                </button>
                <input type="hidden" name="holiday_dates[]" value="${dateValue}">
                <input type="hidden" name="holiday_remarks[]" value="${remarkText}">
            </div>
        `;

        // Add the new item
        $('#selectedHolidayDatesList').append(dateHtml);
        
        // Sort all items by date
        sortHolidayDates();
        
        updateHolidayDateCount();
        removeEmptyMessage('holiday');
    }

    function sortHolidayDates() {
        var container = $('#selectedHolidayDatesList');
        var items = container.children('.date-item').get();
        
        // Sort items by date
        items.sort(function(a, b) {
            var dateA = new Date($(a).data('date'));
            var dateB = new Date($(b).data('date'));
            return dateA - dateB;
        });
        
        // Re-append sorted items
        $.each(items, function(index, item) {
            container.append(item);
        });
    }

    function removeHolidayDate(element) {
        $(element).closest('.date-item').fadeOut(200, function() {
            $(this).remove();
            updateHolidayDateCount();
            checkEmptyList('holiday');
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

    // ============ SPECIFIC DATES FUNCTIONS ============
    
    function addSpecificDate(dateValue, remarkValue) {
        // Check if date already exists
        var exists = false;
        $('#selectedDatesList .date-item').each(function() {
            if ($(this).data('date') === dateValue) {
                exists = true;
                return false;
            }
        });

        if (exists) {
            Swal.fire({
                icon: 'warning',
                title: 'Date Required',
                text: 'This date is already added to the Specific Off Dates list.',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Format date to d-m-Y
        var formattedDate = formatDateDMY(dateValue);
        var dayName = getDayOfWeek(dateValue);
        var remarkText = remarkValue || '';

        // Create date item HTML
        var dateHtml = `
            <div class="date-item" data-date="${dateValue}">
                <div class="date-text">
                    <span class="day-badge">${dayName}</span>
                    <span>${formattedDate}</span>
                    <span class="date-remark">${remarkText}</span>
                </div>
                <button type="button" class="remove-date" onclick="removeSpecificDate(this)" title="Remove date">
                    <i class="fas fa-minus-circle"></i>
                </button>
                <input type="hidden" name="specific_dates[]" value="${dateValue}">
                <input type="hidden" name="specific_remarks[]" value="${remarkText}">
            </div>
        `;

        // Add the new item
        $('#selectedDatesList').append(dateHtml);
        
        // Sort all items by date
        sortSpecificDates();
        
        updateDateCount();
        removeEmptyMessage('specific');
    }

    function sortSpecificDates() {
        var container = $('#selectedDatesList');
        var items = container.children('.date-item').get();
        
        // Sort items by date
        items.sort(function(a, b) {
            var dateA = new Date($(a).data('date'));
            var dateB = new Date($(b).data('date'));
            return dateA - dateB;
        });
        
        // Re-append sorted items
        $.each(items, function(index, item) {
            container.append(item);
        });
    }

    function removeSpecificDate(element) {
        $(element).closest('.date-item').fadeOut(200, function() {
            $(this).remove();
            updateDateCount();
            checkEmptyList('specific');
        });
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

    // ============ UTILITY FUNCTIONS ============
    
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

    function updateWeeklyCount() {
        var count = $('.weekly-checkbox-group input[type="checkbox"]:checked').length;
        $('#weeklyCount').text(count + ' days');
    }

    function getTodayDate() {
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var yyyy = today.getFullYear();
        return yyyy + '-' + mm + '-' + dd;
    }
</script>
@endpush

@section('title', 'Create Holiday Master')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        @include('partials.message-bag')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-calendar-alt"></i> Create Holiday Master</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('holiday-settings.store') }}" method="POST">
                    @csrf

                    <div class="row">

                        <!-- Location -->
                        <div class="col-md-6 mb-3">
                            <label>Location <span class="text-danger">*</span></label>
                            <select name="location_id" class="form-control" required>
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                <option value="{{ $location->id }}"
                                    {{ old('location_id') == $location->id ? 'selected' : '' }} selected>
                                    {{ $location->name }}
                                </option>
                                @endforeach
                            </select>

                            @error('location_id')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Year -->
                        <div class="col-md-6 mb-3">
                            <label>Calendar Year <span class="text-danger">*</span></label>
                            <select name="year" class="form-control" required>
                                <option value="">Select Calendar Year</option>

                                @for($year = date('Y'); $year <= date('Y') + 1; $year++)
                                    <option value="{{ $year }}"
                                    {{ old('year', date('Y')) == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                    </option>
                                    @endfor
                            </select>

                            @error('year')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label>Select Holiday Type <span class="text-danger">*</span></label>
                            <div class="holiday-type-selector">

                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="holiday_type" id="holidayType1" value="holiday" autocomplete="off" hidden>
                                    <label class="btn btn-outline-primary" for="holidayType1">
                                        <i class="fas fa-calendar-day"></i> Holiday
                                    </label>

                                    <input type="radio" class="btn-check" name="holiday_type" id="holidayType2" value="weekly_off" autocomplete="off" hidden>
                                    <label class="btn btn-outline-primary" for="holidayType2">
                                        <i class="fas fa-calendar-week"></i> Weekly Off
                                    </label>

                                    <input type="radio" class="btn-check" name="holiday_type" id="holidayType3" value="specific_date" autocomplete="off" hidden>
                                    <label class="btn btn-outline-primary" for="holidayType3">
                                        <i class="fas fa-calendar-alt"></i> Specific Off Dates
                                    </label>
                                </div>
                            </div>
                            @error('holiday_type')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Holiday Dates Section -->
                    <div id="holidayDatesSection" style="display:none;">
                        <div class="section-card">
                            <div class="card-header">
                                <i class="fas fa-calendar-check"></i> Holiday Dates
                                <span id="holidayDateCount" class="selected-dates-badge">0 dates</span>
                            </div>

                            <div class="card-body">

                                <!-- Quick Holiday Selection -->
                                <div class="mb-4">
                                    <label class="font-weight-bold mb-2">
                                        <i class="fas fa-bolt text-warning"></i>
                                        Quick Holiday Selection
                                    </label>

                                    <div class="holiday-buttons">
                                        <button type="button" class="holiday-btn"
                                            data-date="2026-01-01"
                                            data-remark="New Year's Day">
                                            <br>
                                            <strong>1 Jan</strong><br>
                                            <small>New Year's Day</small>
                                        </button>

                                        <button type="button" class="holiday-btn"
                                            data-date="2026-01-26"
                                            data-remark="Republic Day">
                                            <br>
                                            <strong>26 Jan</strong><br>
                                            <small>Republic Day</small>
                                        </button>

                                        <button type="button" class="holiday-btn"
                                            data-date="2026-05-01"
                                            data-remark="Labour Day">
                                            <br>
                                            <strong>1 May</strong><br>
                                            <small>Labour Day</small>
                                        </button>

                                        <button type="button" class="holiday-btn"
                                            data-date="2026-08-15"
                                            data-remark="Independence Day">
                                            <br>
                                            <strong>15 Aug</strong><br>
                                            <small>Independence Day</small>
                                        </button>

                                        <button type="button" class="holiday-btn"
                                            data-date="2026-10-02"
                                            data-remark="Gandhi Jayanti">
                                            <br>
                                            <strong>2 Oct</strong><br>
                                            <small>Gandhi Jayanti</small>
                                        </button>

                                    </div>
                                </div>

                                <hr>

                                <!-- Manual Entry -->
                                <label class="font-weight-bold mb-2">
                                    <i class="fas fa-plus-circle text-primary"></i>
                                    Add Custom Holiday
                                </label>

                                <div class="row">

                                    <div class="col-md-4">
                                        <label>Date</label>
                                        <input type="date"
                                            id="holidayDateInput"
                                            class="form-control"
                                            min="{{ date('Y-m-d') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label>Holiday Occassion</label>
                                        <input type="text"
                                            id="holidayRemarkInput"
                                            class="form-control"
                                            placeholder="Enter Holiday Name">
                                    </div>

                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button"
                                            id="addHolidayDate"
                                            class="btn btn-primary btn-block">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>

                                </div>

                                <hr>

                                <label class="font-weight-bold">
                                    <i class="fas fa-list"></i>
                                    Selected Holidays
                                </label>

                                <div id="selectedHolidayDatesList" class="date-list-container mt-2">
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
                            </div>
                        </div>
                    </div>

                    <!-- Specific Off Dates Section -->
                    <div id="specificDateSection" style="display: none;">
                        <div class="section-card">
                            <div class="card-header">
                                <i class="fas fa-calendar-check"></i> Specific Off Dates
                                <span id="dateCount" class="selected-dates-badge">0 dates</span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="date-input-group">
                                            <span class="input-group-label">Date</span>
                                            <input type="date"
                                                id="specificDateInput"
                                                class="form-control date-input"
                                                min="{{ date('Y-m-d') }}">

                                            <span class="input-group-label">Reason</span>
                                            <input type="text"
                                                id="specificRemarkInput"
                                                class="form-control remark-input"
                                                placeholder="Enter Reason (optional)">

                                            <button type="button" id="addDate" class="btn btn-primary btn-add-date">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </div>
                                        <small class="text-muted">Press Enter to add</small>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="font-weight-bold">Selected Dates</label>
                                    <div id="selectedDatesList" class="date-list-container">
                                        @if(old('specific_dates'))
                                        @php
                                        $specificDates = old('specific_dates', []);
                                        $specificRemarks = old('specific_remarks', []);
                                        $specificCount = count($specificDates);
                                        @endphp
                                        @for($i = 0; $i < $specificCount; $i++)
                                            <div class="date-item" data-date="{{ $specificDates[$i] }}">
                                            <div class="date-text">
                                                <span class="day-badge">{{ date('D', strtotime($specificDates[$i])) }}</span>
                                                <span>{{ date('d-m-Y', strtotime($specificDates[$i])) }}</span>
                                                <span class="date-remark">{{ $specificRemarks[$i] ?? '' }}</span>
                                            </div>
                                            <button type="button" class="remove-date" onclick="removeSpecificDate(this)">
                                                <i class="fas fa-minus-circle"></i>
                                            </button>
                                            <input type="hidden" name="specific_dates[]" value="{{ $specificDates[$i] }}">
                                            <input type="hidden" name="specific_remarks[]" value="{{ $specificRemarks[$i] ?? '' }}">
                                    </div>
                                    @endfor @endif
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