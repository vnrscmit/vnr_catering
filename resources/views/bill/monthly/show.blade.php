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
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: #f5fafc;
        color: #111827;
    }

    .bill-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 22px 32px 35px;
    }

    /* =========================
           HEADER
        ========================== */

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 30px;
    }

    .header-left h1 {
        margin: 0 0 8px;
        font-size: 34px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }

    .header-left p {
        margin: 0;
        color: #46515c;
        font-size: 16px;
    }

    .header-info {
        display: flex;
        gap: 20px;
    }

    .info-box {
        min-width: 145px;
        padding: 16px 20px;
        background: #ffffff;
        border: 1px solid #b7c8b3;
        border-radius: 6px;
    }

    .info-box span {
        display: block;
        color: #555;
        font-size: 14px;
        margin-bottom: 3px;
    }

    .info-box strong {
        display: block;
        font-size: 20px;
        font-weight: 600;
        color: #111827;
    }

    /* =========================
           TOP CARDS
        ========================== */

    .metrics-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 32px;
    }

    .metric-card {
        background: #ffffff;
        border: 1px solid #b7c8b3;
        border-radius: 10px;
        padding: 20px;
    }

    .card-title {
        display: flex;
        align-items: center;
        gap: 18px;
        padding-bottom: 17px;
        border-bottom: 1px solid #b7c8b3;
        margin-bottom: 20px;
    }

    .card-title i {
        font-size: 25px;
        color: #008b22;
        width: 28px;
        text-align: center;
    }

    .card-title h2 {
        margin: 0;
        font-size: 21px;
        font-weight: 600;
    }

    .metric-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-height: 55px;
        font-size: 16px;
    }

    .metric-row .value {
        font-size: 16px;
        color: #222;
    }

    /* Green highlighted row */

    .metric-highlight-green {
        display: flex;
        justify-content: space-between;
        align-items: center;

        background: #98ef91;
        border-radius: 5px;
        padding: 15px 18px;
        margin-top: 8px;
        font-weight: 600;
    }

    .metric-highlight-green .value {
        font-size: 23px;
        font-weight: 600;
        color: #14651e;
    }

    /* Financial */

    .metric-highlight-orange {
        display: flex;
        justify-content: space-between;
        align-items: center;

        background: #d99600;
        border-radius: 5px;
        padding: 16px 14px;
        margin-top: 8px;

        font-size: 16px;
        font-weight: 600;
    }

    .metric-highlight-orange .value {
        font-size: 22px;
        color: #111;
    }

    .metric-highlight-red {
        display: flex;
        justify-content: space-between;
        align-items: center;

        background: #ffd6d3;
        border-radius: 5px;
        padding: 16px 14px;
        margin-top: 16px;

        font-size: 16px;
        font-weight: 600;
        color: #a90000;
    }

    .metric-highlight-red .value {
        font-size: 21px;
        color: #a90000;
    }

    /* =========================
           TABLE SECTION
        ========================== */

    .statement-title {
        font-size: 22px;
        font-weight: 600;
        margin: 0 0 17px;
    }

    .table-wrapper {
        background: #ffffff;
        border: 1px solid #b7c8b3;
        border-radius: 9px;
        overflow: hidden;
    }

    .expense-table {
        width: 100%;
        border-collapse: collapse;
    }

    .expense-table thead {
        background: #dff3fa;
    }

    .expense-table th {
        padding: 15px 20px;
        text-align: left;
        font-weight: 500;
        font-size: 15px;
        white-space: nowrap;
    }

    .expense-table td {
        padding: 15px 20px;
        border-top: 1px solid #e2e8e8;
        font-size: 15px;
    }

    .expense-table tbody tr {
        height: 56px;
    }

    .expense-table td.center,
    .expense-table th.center {
        text-align: center;
    }

    .expense-table td.right,
    .expense-table th.right {
        text-align: right;
    }

    .expense-table .name {
        font-size: 16px;
        white-space: nowrap;
    }

    .balance-red {
        color: #e00000;
    }

    .balance-green {
        color: #008b22;
    }

    .total-row {
        background: #dff3fa;
        border-top: 2px solid #53715c;
    }

    .total-row td {
        font-size: 15px;
        padding-top: 14px;
        padding-bottom: 14px;
    }

    .total-row .grand-total {
        font-size: 17px;
        font-weight: 600;
    }

    .total-row .green-total {
        color: #008b22;
    }

    /* =========================
           FOOTER ACTIONS
        ========================== */

    .footer-section {
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #dce5e7;

        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .verify-message {
        display: flex;
        align-items: center;
        gap: 15px;
        color: #39434a;
        font-size: 16px;
    }

    .verify-message i {
        font-size: 20px;
        color: #263238;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .btn {
        min-width: 185px;
        height: 56px;
        border-radius: 16px;

        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;

        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .btn i {
        font-size: 18px;
    }

    .btn-edit {
        background: #ffffff;
        color: #008b22;
        border: 2px solid #008b22;
    }

    .btn-edit:hover {
        background: #effbf1;
    }

    .btn-submit {
        background: #008b22;
        color: #ffffff;
        border: 2px solid #008b22;
    }

    .btn-submit:hover {
        background: #00751d;
    }

    /* =========================
           RESPONSIVE
        ========================== */

    @media (max-width: 900px) {

        .bill-container {
            padding: 20px;
        }

        .page-header {
            flex-direction: column;
            gap: 20px;
        }

        .header-info {
            width: 100%;
        }

        .info-box {
            flex: 1;
        }

        .metrics-grid {
            grid-template-columns: 1fr;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .expense-table {
            min-width: 950px;
        }

        .footer-section {
            flex-direction: column;
            align-items: stretch;
            gap: 25px;
        }

        .action-buttons {
            justify-content: flex-end;
        }
    }

    @media (max-width: 600px) {

        .bill-container {
            padding: 15px;
        }

        .header-left h1 {
            font-size: 27px;
        }

        .header-left p {
            font-size: 14px;
        }

        .header-info {
            flex-direction: column;
            gap: 10px;
        }

        .info-box {
            width: 100%;
        }

        .metric-card {
            padding: 15px;
        }

        .card-title h2 {
            font-size: 19px;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn {
            width: 100%;
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
@endpush

@section('title', 'Monthly Bill Preview')
@section('content')



<div class="bill-container">

    <!-- ================= HEADER ================= -->

    <div class="page-header">
        <div class="header-left">
            <h1>Generate Bill Preview</h1>
            <p>Review group settlement details before final submission.</p>
        </div>

        <div class="header-info">

            <div class="info-box">
                <span>Billing Month</span>
                <strong>{{ \Carbon\Carbon::parse($bill->generate_month)->format('F Y') }}</strong>
            </div>

            <div class="info-box">
                <span>Billing Date</span>
                <strong>{{ \Carbon\Carbon::parse($bill->generate_date)->format('d-m-Y') }}</strong>
            </div>

        </div>

    </div>

    <!-- ================= METRICS ================= -->

    <div class="metrics-grid">

        <!-- DIET METRICS -->

        <div class="metric-card">

            <div class="card-title">
                <i class="fas fa-utensils"></i>
                <h2>Diet Metrics</h2>
            </div>

            <div class="metric-row">
                <span>Total Diet</span>
                <span class="value">{{ $bill->total_diets }}</span>
            </div>

            <div class="metric-row">
                <span>Individual Settlement Diet</span>
                <span class="value">{{ $bill->individual_set_diet }}</span>
            </div>

            <div class="metric-row">
                <span>Guest Diet</span>
                <span class="value">{{ $bill->guest_diet }}</span>
            </div>

            <div class="metric-row">
                <span>Non Member Diet</span>
                <span class="value">{{ $bill->non_member_diet }}</span>
            </div>

            <div class="metric-row">
                <span>President Diet</span>
                <span class="value">{{ $bill->president_diet }}</span>
            </div>

            <div class="metric-highlight-green">
                <span>Net Chargeable Diet</span>
                <span class="value">{{ $bill->net_chargeable_diet }}</span>
            </div>

        </div>


        <!-- FINANCIAL METRICS -->

        <div class="metric-card">

            <div class="card-title">
                <i class="fas fa-wallet"></i>
                <h2>Financial Metrics</h2>
            </div>

            <div class="metric-row">
                <span>Total Expense</span>
                <span class="value">{{ $bill->total_expenses }}</span>
            </div>

            <div class="metric-row">
                <span>Individual Settlement Charges</span>
                <span class="value">{{ $bill->individual_expenses }}</span>
            </div>

            <div class="metric-row">
                <span>Guest Charges</span>
                <span class="value">{{ $bill->guest_expenses }}</span>
            </div>

            <div class="metric-row">
                <span>Non Member Charges</span>
                <span class="value">{{ $bill->non_member_expenses }}</span>
            </div>

            <div class="metric-highlight-orange">
                <span>Net Monthly Expense</span>
                <span class="value">{{ $bill->net_monthly_expenses }}</span>
            </div>

            <div class="metric-highlight-red">
                <span>Per Diet Calculation</span>
                <span class="value">{{ $bill->per_diet_calculation_auto }}</span>
            </div>

            <div class="metric-highlight-red">
                <span>Chargeable Rate</span>
                <span class="value">{{ $bill->per_diet_calculation }}</span>
            </div>

        </div>

    </div>

    <!-- ================= EXPENSE TABLE ================= -->

    <h2 class="statement-title">Canteen Monthly Bill Statement</h2>

    <div class="table-wrapper">

        <table class="expense-table">

            <thead>
                <tr>
                    <th>S. No.</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th class="center">Attendance Days</th>
                    <th class="center">Rate</th>
                    <th class="center">Amount</th>
                    <th class="center">Previous Balance</th>
                    <th class="center">Total Amount Due</th>
                </tr>
            </thead>

            <tbody>
                @foreach($bill->details as $data)
                <tr>
                    <td>{{ $loop->iteration }}</td>

                    <td class="name">
                        {{ $data->user?->first_name }}
                    </td>

                    <td class="role">
                        {{ $data->role }}
                    </td>

                    <td class="center">
                        {{ $data->user_diets }}
                    </td>

                    <td class="center">
                        {{ $data->rate_per_diet }}
                    </td>

                    <td class="center">
                        {{ $data->bill_amount }}
                    </td>

                    <td class="center balance-red">
                        {{ $data->pre_balance }}
                    </td>

                    <td class="center grand-total">
                        {{ $data->balance }}
                    </td>
                </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr class="total-row">

                    <td></td>

                    <td class="right">
                        Grand Total:
                    </td>

                    <td></td>

                    <td class="center grand-total">
                        {{ $bill->details->sum('user_diets') }}
                    </td>

                    <td></td>

                    <td class="center grand-total">
                        {{ $bill->details->sum('bill_amount') }}
                    </td>

                    <td class="center balance-red">
                        0
                    </td>

                    <td class="center grand-total green-total">
                        {{ $bill->details->sum('balance') }}
                    </td>

                </tr>
            </tfoot>

        </table>

    </div>
</div>

@endsection