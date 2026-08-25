@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">

<style>
    .statement-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #333;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .center {
        text-align: center !important;
    }

    .right {
        text-align: right !important;
    }

    .name {
        font-weight: 500;
    }

    .role {
        color: #6c757d;
    }

    .balance-red {
        color: #dc3545;
        font-weight: 500;
    }

    .grand-total {
        font-weight: 600;
    }

      .payment-done {
        color:  #28a745;
        font-weight: 500;
    }

    .green-total {
        color: #28a745;
    }

    .total-row {
        background-color: #f8f9fa;
        font-weight: bold;
    }

    .total-row td {
        border-top: 2px solid #dee2e6 !important;
    }

    /* DataTable customization */
    .data-table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .data-table tbody td {
        vertical-align: middle;
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

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // Jab bhi Pay button click ho
        $(document).on('click', '.pay-btn', function() {
            // Button se data lein
            var id = $(this).data('id');
            var name = $(this).data('name');
            var amount = $(this).data('amount');

            // Modal ke fields mein data set karein
            $('#bill_detail_id').val(id);
            $('#userName').val(name);
            $('#payableAmount').val(amount);
            $('#payable_amount').val(amount);
            // Receive amount clear karein
            $('#receive_amount').val('');
        });
    });


    $(document).ready(function() {
        // CSRF token setup for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Payment form submit handler
        $('#paymentForm').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var submitBtn = $('#submitPayment');
            var spinner = $('#paymentSpinner');
            var btnText = $('#paymentBtnText');
            var formData = new FormData(this);

            // Validation: Check if receive amount is entered
            var receiveAmount = $('#receive_amount').val();
            if (!receiveAmount || parseFloat(receiveAmount) <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Amount',
                    text: 'Please enter a valid receive amount greater than 0',
                });
                return false;
            }

            // Show loading state
            submitBtn.prop('disabled', true);
            spinner.removeClass('d-none');
            btnText.text('Processing...');

            $.ajax({
                url: "{{ route('payment.store') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    // Reset loading state
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                    btnText.html('<i class="fas fa-check-circle"></i> Confirm Payment');

                    if (response.success) {
                        // Close modal
                        $('#paymentModal').modal('hide');

                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Payment Successful!',
                            text: response.message || 'Payment has been processed successfully.',
                            timer: 3000,
                            showConfirmButton: true
                        }).then(function() {
                            // Reload page to update table
                            location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    // Reset loading state
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                    btnText.html('<i class="fas fa-check-circle"></i> Confirm Payment');

                    // Parse error response
                    var errorMessage = 'Something went wrong!';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        // Validation errors
                        var errors = xhr.responseJSON.errors;
                        var errorList = [];
                        $.each(errors, function(key, value) {
                            errorList.push(value[0]);
                        });
                        errorMessage = errorList.join('<br>');
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Payment Failed!',
                        html: errorMessage,
                        confirmButtonColor: '#d33',
                    });
                }
            });
        });

        // Modal data population (existing code)
        $(document).on('click', '.pay-btn', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var amount = $(this).data('amount');

            $('#bill_detail_id').val(id);
            $('#userName').val(name || 'N/A');
            $('#payableAmount').val(amount ? amount : '0');
            $('#payable_amount').val(amount || 0);
            $('#receive_amount').val('');
            $('#balanceMessage').text('Payable Amount: ' + (amount ? parseFloat(amount).toFixed(2) : '0.00'));
        });

        // Real-time balance calculation
        $('#receive_amount').on('keyup change', function() {
            var payable = parseFloat($('#payable_amount').val()) || 0;
            var receive = parseFloat($(this).val()) || 0;
            var balance = payable - receive;

            if (balance > 0) {
                $('#balanceMessage').html('<span class="text-danger">Remaining Balance: ' + balance.toFixed(2) + '</span>');
            } else if (balance < 0) {
                $('#balanceMessage').html('<span class="text-warning">Overpayment: ' + Math.abs(balance).toFixed(2) + ' (Change to return)</span>');
            } else if (balance === 0 && receive > 0) {
                $('#balanceMessage').html('<span class="text-success">Payment fully settled! ✅</span>');
            } else {
                $('#balanceMessage').text('Payable Amount: ' + payable.toFixed(2));
            }
        });
    });

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
        // Initialize DataTable
        var table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('payment.getPaymentData') }}", // Create this route in your controller
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'role',
                    name: 'role'
                },
                {
                    data: 'user_diets',
                    name: 'user_diets',
                    className: 'center'
                },
                {
                    data: 'rate_per_diet',
                    name: 'rate_per_diet',
                    className: 'center'
                },
                {
                    data: 'bill_amount',
                    name: 'bill_amount',
                    className: 'center'
                },
                {
                    data: 'previous_balance',
                    name: 'previous_balance',
                    className: 'center balance-red',
                    defaultContent: '0'
                },
                {
                    data: 'total_amount_due',
                    name: 'total_amount_due',
                    className: 'center grand-total'
                },

                {
                    data: 'payment_amount',
                    name: 'payment_amount',
                    className: 'center grand-total'
                },

                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'center'
                }


            ],
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            order: [
                [1, 'asc']
            ],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                zeroRecords: "No records found",
            },
            // dom: 'lBfrtip',
            // buttons: [
            //     'excel',
            //     'pdf',
            //     'print'
            // ],
            // Add footer callback for grand totals
            footerCallback: function(row, data, start, end, display) {
                var api = this.api();

                // Calculate totals for columns 3, 5, 7 (Attendance Days, Bill Amount, Total Amount Due)
                var totalDays = api
                    .column(3, {
                        page: 'current'
                    })
                    .data()
                    .reduce(function(a, b) {
                        return parseInt(a) + parseInt(b);
                    }, 0);

                var totalAmount = api
                    .column(5, {
                        page: 'current'
                    })
                    .data()
                    .reduce(function(a, b) {
                        return parseFloat(a) + parseFloat(b);
                    }, 0);

                var totalDue = api
                    .column(7, {
                        page: 'current'
                    })
                    .data()
                    .reduce(function(a, b) {
                        return parseFloat(a) + parseFloat(b);
                    }, 0);

                // Update footer
                $(api.column(3).footer()).html(totalDays);
                $(api.column(5).footer()).html(totalAmount);
                $(api.column(7).footer()).html(totalDue);
            }
        });

        // Description counter
        const maxLength = 500;
        const description = $('#description');
        const counter = $('#descriptionCount');

        function updateDescriptionCount() {
            const currentLength = description.val().length;
            const remaining = maxLength - currentLength;
            counter.text(remaining);

            if (remaining <= 50) {
                counter.removeClass('text-muted').addClass('text-danger');
            } else {
                counter.removeClass('text-danger').addClass('text-muted');
            }
        }

        description.on('input', function() {
            updateDescriptionCount();
        });

        updateDescriptionCount();
    });
</script>
@endpush

@section('title', 'Create Payment')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-money-bill-wave me-1"></i> Bill Payment
                </h5>
                <a href="{{ route('payment.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('payment.store') }}" method="POST">
                    @csrf
                    <!-- ================= DATA TABLE ================= -->
                    <div class="">
                        <div class="table-wrapper">
                            <table class="table table-bordered data-table" id="payment-table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 15%;">Name</th>
                                        <th style="width: 10%;">Type</th>
                                        <th class="center" style="width: 10%;">Attendance Days</th>
                                        <th class="center" style="width: 8%;">Rate</th>
                                        <th class="center" style="width: 10%;">Amount</th>
                                        <th class="center" style="width: 12%;">Previous Balance</th>
                                        <th class="center" style="width: 12%;">Total Amount Due</th>
                                        <th class="center" style="width: 8%;">Payment Amount</th>
                                        <th class="center" style="width: 12%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data loaded via DataTables AJAX -->
                                </tbody>
                                <tfoot>
                                    <tr class="total-row">
                                        <td></td>
                                        <td class="right">Grand Total:</td>
                                        <td></td>
                                        <td class="center grand-total" id="totalDays">0</td>
                                        <td></td>
                                        <td class="center grand-total" id="totalAmount">0.00</td>
                                        <td class="center balance-red">0</td>
                                        <td class="center grand-total green-total" id="totalDue">0.00</td>
                                        <td></td>
                                        <td></td> <!-- Empty footer for action column -->
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <!-- <div class="d-flex justify-content-end mt-4">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Submit
                            </button>

                            <button type="reset" class="btn" onclick="resetFormWithSweetAlert()" style="background-color: #F2BB1E; border-color: #F2BB1E; color: white;">
                                <i class="fa fa-undo"></i> Reset
                            </button>
                            <a href="{{ route('payment.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div> -->
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">
                    <i class="fas fa-hand-holding-usd me-2"></i> Make Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentForm" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="bill_detail_id" id="bill_detail_id">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                        <input type="text"
                            class="form-control"
                            id="userName"
                            readonly
                            style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount Due <span class="text-danger">*</span></label>
                        <input type="text"
                            class="form-control"
                            id="payableAmount"
                            readonly
                            style="background-color: #e9ecef; cursor: not-allowed;">
                        <input type="hidden" name="payable_amount" id="payable_amount">
                    </div>

                    <div class="mb-3">
                        <label for="receive_amount" class="form-label fw-bold">Receive Amount <span class="text-danger">*</span></label>
                        <input type="number"
                            name="receive_amount"
                            id="receive_amount"
                            class="form-control form-control-lg"
                            step="0.01"
                            min="0"
                            required
                            placeholder="Enter receive amount">
                        <small class="text-muted">Enter the amount you are receiving</small>
                    </div>

                    <div class="mb-3">
                        <label for="payment_date" class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
                        <input type="date"
                            name="payment_date"
                            id="payment_date"
                            class="form-control"
                            min="{{ date('Y-m-01') }}"
                            max="{{ date('Y-m-d') }}"
                            value="{{ date('Y-m-d') }}"
                            required>
                        <small class="text-muted">Select date from {{ date('d-m-Y', strtotime(date('Y-m-01'))) }} to {{ date('d-m-Y') }}</small>
                    </div>

                    <div class="mb-3">
                        <label for="payment_note" class="form-label">Payment Note</label>
                        <textarea name="payment_note"
                            id="payment_note"
                            class="form-control"
                            rows="2"
                            placeholder="Optional notes about payment"></textarea>
                    </div>
                </div>
                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>

                    <button type="submit" class="btn btn-primary" id="submitPayment">
                        <i class="fas fa-save"></i> Submit
                    </button>

                </div>
            </form>
        </div>
    </div>
</div>

@endsection