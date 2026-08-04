@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
<!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="styl esheet"> -->
<link rel="stylesheet" href="/admin_resources/css/small-box.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        let currentDate = moment();
        let calendarData = null;

        function renderCalendar(date, data) {
            const month = date.month();
            const year = date.year();
            const firstDay = moment({
                year: year,
                month: month,
                day: 1
            });
            const lastDay = moment({
                year: year,
                month: month,
                day: 1
            }).endOf('month');
            const daysInMonth = lastDay.date();
            const startDayOfWeek = firstDay.day();

            $('#currentMonthYear').text(firstDay.format('MMMM YYYY'));

            let gridHtml = '';
            const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            dayNames.forEach((name, index) => {
                let headerClass = '';
                if (index === 0) headerClass = 'sunday';
                gridHtml += `<div class="day-header ${headerClass}">${name}</div>`;
            });

            for (let i = 0; i < startDayOfWeek; i++) {
                gridHtml += `<div class="day-cell empty"></div>`;
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const currentDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const todayStr = moment().format('YYYY-MM-DD');
                const isToday = currentDateStr === todayStr;
                const dayOfWeek = moment(currentDateStr).day();
                const isSunday = dayOfWeek === 0;

                let dayData = null;
                let statusClass = '';
                let statusText = '';
                let lockIcon = '';
                let isLocked = false;
                let absentFlag = 0;
                let openFlag = 0;

                // Process all days including Sundays
                if (data && data.data) {
                    const allDays = [
                        ...(data.data.previous_month?.previousdays || []),
                        ...(data.data.current_month?.currentdays || []),
                        ...(data.data.next_month?.nextdays || [])
                    ];
                    dayData = allDays.find(d => d.date === currentDateStr);
                    
                    if (dayData) {
                        absentFlag = dayData.absent_flag || 0;
                        openFlag = dayData.open_flag || 0;
                        isLocked = (openFlag == 1 && dayData.date < moment().format('YYYY-MM-DD'));

                        if (isLocked) {
                            lockIcon = 'fa-lock';
                        }

                        // Show status only if open_flag = 1 (for all days including Sundays)
                        if (openFlag == 1) {
                            if (absentFlag == 1) {
                                statusClass = 'absent';
                                statusText = 'Absent';
                            } else if (absentFlag == 0) {
                                statusClass = 'present';
                                statusText = 'Present';
                            }
                        }

                        if (isLocked) {
                            statusClass += ' locked';
                        }
                    }
                }

                if (isToday) {
                    statusClass += ' today';
                }

                let statusTextHtml = statusText ? `<div class="status-text ${statusClass.split(' ')[0]}-text">${statusText}</div>` : '';
                let lockIconHtml = lockIcon ? `<i class="fas ${lockIcon} lock-icon"></i>` : '';
                
                // For Sundays, show the Sunday number style but allow interactions
                const dayNumberClass = isSunday ? 'sunday-number' : '';
                
                // Determine if the cell should be clickable
                // Cell is clickable if open_flag = 1 and not locked (for all days including Sundays)
                const isClickable = !isLocked && openFlag == 1;
                
                // Add data attributes including sunday flag
                const dataAttrs = `data-date="${currentDateStr}" data-status="${statusClass}" data-absent="${absentFlag}" data-open="${openFlag}" data-locked="${isLocked}" data-sunday="${isSunday ? 1 : 0}"`;

                // Build the cell with appropriate classes
                let cellClasses = 'day-cell';
                if (statusClass) cellClasses += ` ${statusClass}`;
                if (isSunday) cellClasses += ' sunday-cell';
                if (!isClickable && !isSunday) cellClasses += ' not-clickable';
                if (isSunday && !isClickable) cellClasses += ' sunday-not-clickable';
                if (isSunday && isClickable) cellClasses += ' sunday-clickable';

                gridHtml += `
                    <div class="${cellClasses}" ${dataAttrs}>
                        <div class="day-number ${dayNumberClass}">${day}</div>
                        ${statusTextHtml}
                        ${lockIconHtml}
                    </div>
                `;
            }

            $('#calendarGrid').html(gridHtml);

            if (data && data.data) {
                const currentSummary = data.data.current_month?.currentSummary || {
                    present: 0,
                    absent: 0,
                    locked: 0
                };
                $('#presentCount').text(currentSummary.present || 0);
                $('#absentCount').text(currentSummary.absent || 0);
                $('#lockedCount').text(currentSummary.locked || 0);
            }

            // Event handlers for navigation
            $('#prevMonth').off('click').on('click', function() {
                currentDate.subtract(1, 'month');
                fetchCalendarData();
            });

            $('#nextMonth').off('click').on('click', function() {
                currentDate.add(1, 'month');
                fetchCalendarData();
            });

            $('#todayBtn').off('click').on('click', function() {
                currentDate = moment();
                fetchCalendarData();
            });

            // Event handler for day clicks - including Sundays when clickable
            $('.day-cell:not(.empty):not(.locked):not(.not-clickable):not(.sunday-not-clickable)').on('click', function() {
                const $cell = $(this);
                const date = $cell.data('date');
                const currentStatus = $cell.data('status');
                const absent = $cell.data('absent');
                const open = $cell.data('open');
                const isLocked = $cell.data('locked');
                const isSunday = $cell.data('sunday');

                // Skip if locked or empty
                if (isLocked || $cell.hasClass('empty') || $cell.hasClass('locked')) {
                    return;
                }

                // Only allow toggling for days that have open_flag = 1
                if (open != 1) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Not Available',
                        text: 'This day is not available for attendance marking.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                // Determine new status (toggle between present and absent)
                const newAbsentFlag = absent == 1 ? 0 : 1;
                const newStatus = newAbsentFlag == 1 ? 'absent' : 'present';
                const newStatusText = newAbsentFlag == 1 ? 'Absent' : 'Present';

                // Show confirmation with SweetAlert2
                const action = newAbsentFlag == 1 ? 'mark as Absent' : 'mark as Present';
                const color = newAbsentFlag == 1 ? '#dc3545' : '#28a745';
                
                let sundayWarning = '';
                let sundayIcon = '';
                if (isSunday) {
                    sundayWarning = '<p style="color: #dc3545; font-weight: bold; margin-top: 10px;">⚠️ This is a Sunday</p>';
                    sundayIcon = 'fa-calendar-alt';
                }

                Swal.fire({
                    title: 'Confirm Attendance',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>Date:</strong> ${date}</p>
                            ${isSunday ? `<p><strong>Day:</strong> Sunday ${sundayWarning}</p>` : ''}
                            <p><strong>Action:</strong> ${action}</p>
                            <p style="color: ${color}; font-weight: bold;">
                                ${newAbsentFlag == 1 ? '⚠️ This will mark the day as Absent' : '✅ This will mark the day as Present'}
                            </p>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: color,
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: `Yes, ${action}`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateAttendance(date, newAbsentFlag, $cell);
                    }
                });
            });
        }

        function updateAttendance(date, absentFlag, $cell) {
            const locationId = $('#location_id').val();

            // Show loading state
            Swal.fire({
                title: 'Updating...',
                text: 'Please wait while we update the attendance',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "{{ route('attendance.update') }}",
                type: "POST",
                data: {
                    date: date,
                    location_id: locationId,
                    absent_flag: absentFlag,
                    _token: "{{ csrf_token() }}"
                },
                beforeSend: function() {
                    $cell.css('opacity', '0.6');
                },
                success: function(response) {
                    Swal.close();

                    if (response.success) {
                        // Update the cell UI
                        const newStatus = absentFlag == 1 ? 'absent' : 'present';
                        const newStatusText = absentFlag == 1 ? 'Absent' : 'Present';

                        // Remove old status classes
                        $cell.removeClass('present absent');
                        // Add new status class
                        $cell.addClass(newStatus);

                        // Update status text
                        let statusTextHtml = `<div class="status-text ${newStatus}-text">${newStatusText}</div>`;
                        $cell.find('.status-text').remove();

                        // If there's already a status text div, update it, otherwise append
                        if ($cell.find('.status-text').length === 0) {
                            $cell.append(statusTextHtml);
                        } else {
                            $cell.find('.status-text').replaceWith(statusTextHtml);
                        }

                        // Update data attributes
                        $cell.data('status', newStatus);
                        $cell.data('absent', absentFlag);

                        // Refresh calendar data to update summary counts
                        fetchCalendarData();

                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: `Attendance marked as ${newStatusText} for ${date}`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        // SweetAlert for failed update with response message
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to Update Attendance',
                            text: response.message || 'Unknown error occurred. Please try again.',
                            confirmButtonColor: '#dc3545',
                            confirmButtonText: 'OK',
                        });
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    console.error('Error updating attendance:', xhr);

                    let errorMsg = 'Failed to update attendance. Please try again.';
                    let errorTitle = 'Error';

                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        if (xhr.responseJSON.errors) {
                            // If there are validation errors
                            const errors = Object.values(xhr.responseJSON.errors).flat();
                            errorMsg = errors.join('<br>');
                        }
                    }

                    // Handle different HTTP status codes
                    if (xhr.status === 403) {
                        errorTitle = 'Permission Denied';
                        errorMsg = 'You do not have permission to update attendance.';
                    } else if (xhr.status === 404) {
                        errorTitle = 'Not Found';
                        errorMsg = 'The requested resource was not found.';
                    } else if (xhr.status === 422) {
                        errorTitle = 'Validation Error';
                    } else if (xhr.status === 0) {
                        errorTitle = 'Connection Error';
                        errorMsg = 'Unable to connect to the server. Please check your internet connection.';
                    }

                    Swal.fire({
                        icon: 'error',
                        title: errorTitle,
                        html: errorMsg.replace(/\n/g, '<br>'),
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK',
                    });
                },
                complete: function() {
                    $cell.css('opacity', '1');
                }
            });
        }

        function fetchCalendarData() {
            const locationId = $('#location_id').val();
            if (!locationId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Location Selected',
                    text: 'Please select a location to view the calendar.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
                return;
            }

            $.ajax({
                url: "{{ route('calendar.events') }}",
                type: "GET",
                data: {
                    location_id: locationId
                },
                success: function(response) {
                    calendarData = response;
                    renderCalendar(currentDate, response);
                },
                error: function(xhr) {
                    console.error('Error fetching calendar data:', xhr);

                    let errorMsg = 'Failed to load calendar data. Please try again.';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error Loading Calendar',
                        text: errorMsg,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }

        $('#location_id').change(function() {
            currentDate = moment();
            fetchCalendarData();
        });

        // Add CSS styles
        const style = document.createElement('style');
        style.textContent = `
            .day-cell:not(.empty):not(.locked):not(.not-clickable):not(.sunday-not-clickable) {
                cursor: pointer;
            }
            
            .day-cell.locked {
                cursor: not-allowed;
            }
            
            .day-cell.empty {
                cursor: default;
            }
            
            .day-cell.present {
                background: #e8f5e9;
                border-color: #81c784;
            }
            
            .day-cell.absent {
                background: #ffebee;
                border-color: #ef9a9a;
            }
            
            .day-cell.locked {
                opacity: 0.85;
                cursor: not-allowed;
            }
            
            .day-cell.present:hover:not(.locked):not(.not-clickable):not(.sunday-not-clickable) {
                background: #c8e6c9;
                border-color: #66bb6a;
            }
            
            .day-cell.absent:hover:not(.locked):not(.not-clickable):not(.sunday-not-clickable) {
                background: #ffcdd2;
                border-color: #e57373;
            }

            /* Sunday specific styles */
            .day-cell.sunday-cell .day-number.sunday-number {
                color: #dc3545;
                font-weight: 700;
            }

            .day-cell.sunday-cell.present .day-number.sunday-number {
                color: #28a745;
            }

            .day-cell.sunday-cell.absent .day-number.sunday-number {
                color: #dc3545;
            }

            .day-cell.sunday-cell.sunday-clickable:not(.locked):hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                border-color: #ff6b6b;
            }

            .day-cell.sunday-cell.sunday-not-clickable {
                cursor: not-allowed;
                opacity: 0.7;
            }

            .day-cell.sunday-cell.sunday-clickable {
                cursor: pointer;
                border-color: #ffd4d4;
            }

            .day-cell.sunday-cell.present.sunday-clickable {
                background: #e8f5e9;
                border-color: #81c784;
            }

            .day-cell.sunday-cell.absent.sunday-clickable {
                background: #ffebee;
                border-color: #ef9a9a;
            }

            .day-cell.sunday-cell.present.sunday-clickable:hover {
                background: #c8e6c9;
                border-color: #66bb6a;
            }

            .day-cell.sunday-cell.absent.sunday-clickable:hover {
                background: #ffcdd2;
                border-color: #e57373;
            }

            .day-cell.not-clickable {
                cursor: not-allowed;
                opacity: 0.7;
            }

            .day-cell.sunday-cell .status-text.present-text {
                color: #28a745;
            }

            .day-cell.sunday-cell .status-text.absent-text {
                color: #dc3545;
            }

            /* Legend update for Sundays */
            .legend-item .sunday-indicator {
                font-size: 12px;
                color: #dc3545;
                margin-left: 5px;
            }
        `;
        document.head.appendChild(style);

        fetchCalendarData();
    });
</script>
@endpush

<style>
    /* Card Layout Styles - Improved Alignment */
    .card-header {
        background: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
        padding: 20px 25px;
    }

    .card-header .row {
        align-items: center;
        width: 100%;
        margin: 0;
    }

    .card-header .col-md-6 {
        padding: 0 15px;
    }

    .card-header .form-label {
        font-size: 14px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }

    .card-header .form-control {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 10px 15px;
        font-size: 14px;
        transition: all 0.2s;
    }

    .card-header .form-control:focus {
        border-color: #4a90d9;
        box-shadow: 0 0 0 0.2rem rgba(74, 144, 217, 0.25);
    }

    .card-header h5 {
        font-size: 18px;
        font-weight: 600;
        color: #495057;
    }

    /* Card Body with proper spacing */
    .card-body {
        padding: 25px;
        background: #ffffff;
    }

    /* Attendance Cards Container - Proper Alignment */
    .attendance-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 30px;
        width: 100%;
    }

    /* Consistent Card Height */
    .attendance-card {
        background: white;
        border-radius: 12px;
        padding: 20px 25px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        display: flex;
        align-items: center;
        justify-content: flex-start;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid #e9ecef;
        position: relative;
        overflow: hidden;
        height: 100px;
        min-height: 100px;
        width: 100%;
    }

    .attendance-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    }

    .attendance-card .card-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
        flex-shrink: 0;
    }

    .attendance-card .card-icon.present-icon {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .attendance-card .card-icon.absent-icon {
        background: linear-gradient(135deg, #dc3545, #e74c3c);
    }

    .attendance-card .card-icon.locked-icon {
        background: linear-gradient(135deg, #6c757d, #8e9eab);
    }

    .attendance-card .card-content {
        flex: 1;
        margin-left: 15px;
        min-width: 0;
    }

    .attendance-card .card-content .card-label {
        font-size: 13px;
        color: #6c757d;
        font-weight: 500;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .attendance-card .card-content .card-value {
        font-size: 28px;
        font-weight: 700;
        color: #2c3e50;
        line-height: 1.2;
    }

    .attendance-card .card-content .card-value.present-value {
        color: #28a745;
    }

    .attendance-card .card-content .card-value.absent-value {
        color: #dc3545;
    }

    .attendance-card .card-content .card-value.locked-value {
        color: #6c757d;
    }

    .attendance-card .card-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 11px;
        padding: 3px 12px;
        border-radius: 20px;
        background: #f8f9fa;
        color: #6c757d;
        font-weight: 500;
        white-space: nowrap;
    }

    .attendance-card .card-badge.present-badge {
        background: #e8f5e9;
        color: #28a745;
    }

    .attendance-card .card-badge.absent-badge {
        background: #ffebee;
        color: #dc3545;
    }

    .attendance-card .card-badge.locked-badge {
        background: #f5f5f5;
        color: #6c757d;
    }

    /* Calendar Container */
    .calendar-container {
        background: white;
        border-radius: 12px;
        padding: 20px 25px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid #e9ecef;
        margin-top: 10px;
        width: 100%;
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 0 5px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .calendar-nav {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .calendar-nav button {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s;
        color: #495057;
        font-weight: 500;
    }

    .calendar-nav button:hover {
        background: #e9ecef;
        border-color: #dee2e6;
    }

    .calendar-nav .month-year {
        font-size: 18px;
        font-weight: 600;
        min-width: 160px;
        text-align: center;
        color: #2c3e50;
    }


    /* Legend */
    .legend {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        padding: 12px 20px;
        background: #f8f9fa;
        border-radius: 10px;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        border: 1px solid #e9ecef;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #495057;
        font-weight: 500;
    }

    .legend-item .color-box {
        width: 20px;
        height: 20px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
    }

    /* Calendar Grid - Updated */
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
        width: 100%;
    }

    .calendar-grid .day-header {
        text-align: center;
        font-weight: 600;
        padding: 10px 0 8px 0;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
    }

    .calendar-grid .day-header.sunday {
        color: #dc3545;
    }

    .calendar-grid .day-cell {
        padding: 12px 4px;
        border-radius: 10px;
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 80px;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
        position: relative;
        gap: 2px;
    }

    .calendar-grid .day-cell:hover:not(.empty):not(.locked):not(.not-clickable):not(.sunday-not-clickable) {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-color: #4a90d9;
    }

    .calendar-grid .day-cell .day-number {
        font-size: 24px;
        font-weight: 700;
        color: #2c3e50;
        line-height: 1.2;
    }

    .calendar-grid .day-cell .day-number.sunday-number {
        color: #dc3545;
    }

    .calendar-grid .day-cell .status-text {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 2px;
    }

    .calendar-grid .day-cell .status-text.present-text {
        color: #28a745;
    }

    .calendar-grid .day-cell .status-text.absent-text {
        color: #dc3545;
    }

    .calendar-grid .day-cell .lock-icon {
        color: #6c757d;
        font-size: 14px;
        position: absolute;
        top: 6px;
        right: 8px;
        opacity: 0.7;
    }

    .calendar-grid .day-cell.present {
        background: #e8f5e9;
        border-color: #81c784;
    }

    .calendar-grid .day-cell.absent {
        background: #ffebee;
        border-color: #ef9a9a;
    }

    .calendar-grid .day-cell.locked {
        opacity: 0.85;
        cursor: not-allowed;
    }

    .calendar-grid .day-cell.locked.present {
        background: #e8f5e9;
        border-color: #c8e6c9;
    }

    .calendar-grid .day-cell.locked.absent {
        background: #ffebee;
        border-color: #ffcdd2;
    }

    .calendar-grid .day-cell.empty {
        background: transparent;
        border-color: transparent;
        cursor: default;
        min-height: 60px;
    }

    .calendar-grid .day-cell.empty:hover {
        transform: none;
        box-shadow: none;
        border-color: transparent;
    }

    /* Sunday Cell Styles */
    .calendar-grid .day-cell.sunday-cell {
        border-color: #ffd4d4;
    }

    .calendar-grid .day-cell.sunday-cell.sunday-clickable {
        border-color: #ffb3b3;
    }

    .calendar-grid .day-cell.sunday-cell.sunday-clickable:hover {
        border-color: #ff6b6b !important;
    }

    .calendar-grid .day-cell.sunday-cell.sunday-not-clickable {
        opacity: 0.6;
        cursor: not-allowed;
        border-color: #ffd4d4;
    }

    .calendar-grid .day-cell.sunday-cell.present {
        background: #e8f5e9;
        border-color: #81c784;
    }

    .calendar-grid .day-cell.sunday-cell.absent {
        background: #ffebee;
        border-color: #ef9a9a;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .attendance-cards {
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .attendance-card {
            height: 90px;
            min-height: 90px;
            padding: 15px 20px;
        }
    }

    @media (max-width: 768px) {
        .attendance-cards {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .attendance-card {
            height: 85px;
            min-height: 85px;
            padding: 12px 15px;
        }

        .attendance-card .card-content .card-value {
            font-size: 22px;
        }

        .attendance-card .card-icon {
            width: 40px;
            height: 40px;
            font-size: 20px;
        }

        .calendar-grid {
            gap: 5px;
        }

        .calendar-grid .day-cell {
            min-height: 65px;
            padding: 8px 2px;
        }

        .calendar-grid .day-cell .day-number {
            font-size: 18px;
        }

        .calendar-grid .day-cell .status-text {
            font-size: 10px;
        }

        .calendar-grid .day-cell .lock-icon {
            font-size: 12px;
            top: 4px;
            right: 5px;
        }

        .calendar-nav .month-year {
            font-size: 15px;
            min-width: 110px;
        }

        .calendar-nav button {
            padding: 5px 12px;
            font-size: 14px;
        }

        .legend {
            gap: 12px;
            padding: 10px 15px;
            font-size: 12px;
        }

        .legend-item .color-box {
            width: 16px;
            height: 16px;
        }

        .card-body {
            padding: 15px;
        }

        .card-header {
            padding: 15px;
        }

        .calendar-container {
            padding: 15px;
        }
    }

    @media (max-width: 480px) {
        .attendance-cards {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .attendance-card {
            height: 80px;
            min-height: 80px;
            padding: 10px 15px;
        }

        .calendar-grid {
            gap: 4px;
        }

        .calendar-grid .day-cell {
            min-height: 55px;
            padding: 6px 1px;
        }

        .calendar-grid .day-cell .day-number {
            font-size: 15px;
        }

        .calendar-grid .day-cell .status-text {
            font-size: 9px;
        }

        .calendar-grid .day-cell .lock-icon {
            font-size: 10px;
            top: 3px;
            right: 4px;
        }

        .calendar-nav .month-year {
            font-size: 13px;
            min-width: 80px;
        }

        .calendar-nav button {
            padding: 4px 8px;
            font-size: 12px;
        }
    }
</style>

@section('title', 'Calendar')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <label class="form-label fw-bold mb-1">Select Location</label>
                        <select class="form-control form-select" id="location_id">
                            @foreach($locations as $location)
                            <option value="{{ $location['location_id'] }}">
                                {{ $location['location_name'] }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Attendance Summary Cards -->
                <div class="attendance-cards">
                    <div class="attendance-card">
                        <div class="card-icon present-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="card-content">
                            <div class="card-label">Present Days</div>
                            <div class="card-value present-value" id="presentCount">0</div>
                        </div>
                        <div class="card-badge present-badge">
                            <i class="fas fa-check-circle me-1"></i>
                        </div>
                    </div>

                    <div class="attendance-card">
                        <div class="card-icon absent-icon">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="card-content">
                            <div class="card-label">Absent Days</div>
                            <div class="card-value absent-value" id="absentCount">0</div>
                        </div>
                        <div class="card-badge absent-badge">
                            <i class="fas fa-times-circle me-1"></i>
                        </div>
                    </div>

                    <div class="attendance-card">
                        <div class="card-icon locked-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="card-content">
                            <div class="card-label">Locked Days</div>
                            <div class="card-value locked-value" id="lockedCount">0</div>
                        </div>
                        <div class="card-badge locked-badge">
                            <i class="fas fa-lock me-1"></i>
                        </div>
                    </div>
                </div>

                <div class="calendar-container">
                    <!-- Legend -->
                    <div class="legend">
                        <div class="legend-item">
                            <div class="color-box" style="background: #e8f5e9; border-color: #81c784;"></div>
                            <span>Present</span>
                        </div>
                        <div class="legend-item">
                            <div class="color-box" style="background: #ffebee; border-color: #ef9a9a;"></div>
                            <span>Absent</span>
                        </div>
                        <div class="legend-item">
                            <div class="color-box" style="background: #f5f5f5; border-color: #e0e0e0;"></div>
                            <span>Locked <i class="fas fa-lock text-secondary ms-1"></i></span>
                        </div>
                        <div class="legend-item">
                            <div class="color-box" style="background: #ffd4d4; border-color: #ffb3b3;"></div>
                            <span>Sunday <i class="fas fa-calendar-alt text-danger ms-1"></i></span>
                        </div>
                    </div>

                    <!-- Calendar Header -->
                    <div class="calendar-header">
                        <div></div>
                        <div class="calendar-nav">
                            <button id="prevMonth">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="month-year" id="currentMonthYear"></span>
                            <button id="nextMonth">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="calendar-grid" id="calendarGrid"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection