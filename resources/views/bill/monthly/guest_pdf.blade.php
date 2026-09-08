<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reimbursement Claim - Guest Charges</title>
    <style>
        /* Base styles */
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            color: #000;
        }

        /* Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .header-table td {
            border: none;
            padding: 5px 0;
            vertical-align: middle;
        }

        .logo-img {
            max-height: 80px;
            width: auto;
            display: block;
        }

        .main-title {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .sub-title {
            font-size: 15px;
            margin-top: 4px;
            color: #333;
        }

        .bill-info {
            font-size: 12px;
            color: #555;
            line-height: 1.8;
            text-align: right;
        }

        .bill-info strong {
            font-weight: bold;
        }

        .separator {
            border: none;
            border-top: 2px solid #000;
            margin: 0 0 25px 0;
        }

        /* Letter content */
        .letter-content p {
            margin: 8px 0;
        }

        .address-date-block {
            width: 100%;
            border: none;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }

        .address-date-block td {
            border: none;
            vertical-align: top;
            padding: 0;
        }

        .address-block {
            width: 70%;
            text-align: left;
        }

        .date-block {
            width: 30%;
            text-align: right;
            white-space: nowrap;
        }

        .subject {
            margin: 5px 0 15px 0;
        }

        .salutation {
            margin: 15px 0 10px 0;
        }

        .body-text {
            margin: 10px 0;
            text-align: justify;
        }

        .total-amount {
            font-weight: bold;
            margin: 10px 0;
        }

        .amount-in-words {
            font-weight: bold;
        }

        .signature {
            margin-top: 50px;
        }

        .signature p {
            margin: 2px 0;
        }

        .designation {
            font-style: italic;
            color: #555;
        }

        /* Expense details (simple) */
        .expense-details p {
            margin: 4px 0;
        }

        /* --- NEW: Table for second page --- */
        .expense-table-wrapper {
            margin-top: 40px;
            page-break-before: always;
            /* forces new page in print */
        }

        .expense-table-wrapper h3 {
            font-weight: bold;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .expense-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 20px 0;
        }

        .expense-table th,
        .expense-table td {
            border: 1px solid #000;
            padding: 8px 10px;
            text-align: left;
        }

        .expense-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .expense-table .text-right {
            text-align: right;
        }

        .expense-table .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        /* small footer for second page */
        .page-footer {
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            font-size: 11px;
            color: #888;
            text-align: center;
        }



        table {
            border-collapse: collapse;
            width: 100%;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 30px;
            /* text-transform: uppercase; */
        }
    </style>

</head>

<body>
    <!-- Top header with logo and title -->
    <table style="margin-bottom: 5px; border: none; width: 100%; table-layout: fixed; border-collapse: collapse;">
        <tr>
            <td style="border: none; width: 20%; vertical-align: middle; text-align: left; padding: 5px 0;">
                <img src="{{ public_path('assets/images/dashboard_logo_vnr.png') }}"
                    style="max-height: 100px; width: auto; display: inline-block;">
            </td>
            <td style="border: none; width: 60%; vertical-align: middle; text-align: center; padding: 5px 0;">
                <div style="font-size: 20px; font-weight: bold;">
                    Guest Reimbursement Claim
                </div>
                <div style="font-size: 16px; margin-top: 5px;">
                    Month - {{ \Carbon\Carbon::parse($bill->generate_month)->format('F Y') }}
                </div>
            </td>
            <td style="border: none; width: 20%; vertical-align: middle; text-align: left; padding: 5px 0;">
            </td>
        </tr>
    </table>

    <!-- Letter Content -->
    <div class="letter-content">



        <!-- Address Block -->
        <table class="address-date-block">
            <tr>
                <td class="address-block">
                    <p>The Manager</p>
                    <p>Accounts Department</p>
                    <p>VNR Seeds Pvt. Ltd.</p>
                    <p>Corporate Center, Ring Road No. 1,</p>
                    <p>Raipur (C.G.)</p>
                </td>
                <td class="date-block">
                    <strong>Date:</strong>
                    {{ $generatedDate ?? '' }}
                </td>
            </tr>
        </table>

        <!-- Subject -->
        <div class="subject">
            <p><strong>Subject:</strong> Claim for Guest Canteen Charges Reimbursement – {{ $locationName ?? '' }} ({{ $monthYear ?? '' }})</p>
        </div>

        <!-- Salutation -->
        <div class="salutation">
            <p>Dear Sir/Madam,</p>
        </div>

        <!-- Body -->
        <div class="body-text">
            <p>
                This is to request the reimbursement of meal expenses incurred for official guests
                at the {{ $locationName ?? '' }} canteen during the month of {{ $monthYear ?? '' }}.
            </p>
        </div>

        <!-- Expense Details -->
        <div class="body-text">
            <p>The breakdown of the guest charges is as follows:</p>
        </div>

        <!-- Option 1: Simple Text Format -->
        <div class="expense-details">
            <p><strong>Number of Guests:</strong> {{ $guestDiet ?? 0 }}</p>
            <p><strong>Meal Rate per Guest:</strong> {{ $mealRatePerGuest ?? 0 }}</p>
            <p><strong>Total Reimbursement Amount:</strong> {{ $guestExpenses ?? 0  }}</p>
        </div>

        <!-- Amount in Words -->
        <div class="amount-in-words">
            <p><strong>({{ $totalAmountInWords ?? '' }})</strong></p>
        </div>

        <!-- Request -->
        <div class="body-text">
            <p>
                Kindly arrange to credit/reimburse the total amount of <strong>Rs. {{ $guestExpenses ?? 0 }}/-</strong>
                to the canteen account at your earliest convenience.
            </p>
        </div>

        <!-- Closing -->
        <div class="closing">
            <p>Thank you.</p>
        </div>

        <div>
            <p>Yours sincerely,</p>
        </div>

        <!-- Signature -->
        <div class="signature">
            <p><strong>{{ $canteenAdministrator ?? '' }}</strong></p>
            <p class="designation">(Canteen Administrator)</p>
        </div>

        <!-- Footer with page info -->
        <div style="margin-top: 10px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 11px; color: #888; text-align: center;">
            <span>This is a system-generated document</span>
        </div>

    </div>

    <!-- =================== PAGE 2 =================== -->
    <div class="expense-table-wrapper">

        <table class="expense-table">
            <tr>
                <th colspan="6" style="text-align: center; font-size: 16px; background-color: #e0e0e0;">
                    Guest Charges – {{ $monthYear ?? '' }}
                </th>
            </tr>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>No. of Guests</th>
                    <th>Rate</th>
                    <th>Amount</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @php
                $s_no = 1;
                @endphp
                @endphp
                @forelse($guestData ?? [] as $index => $data)
                @php
                if($data->total_guests == 0 || $data->amount == 0){
                continue;
                }
                @endphp
                <tr>
                    <td class="text-center">{{ $s_no++ }}</td>
                    <td>{{ $data->date_formatted ?? $data->date ?? '' }}</td>
                    <td class="text-center">{{ $data->total_guests ?? 0 }}</td>
                    <td class="text-center">{{ $data->rate_per_guest ?? 0 }}</td>
                    <td class="text-center">{{ $data->amount ?? 0 }}</td>
                    <td></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No data found for this month</td>
                </tr>
                @endforelse
                <tr class="total-row">
                    <td colspan="2" style="text-align: right;"><strong>Total</strong></td>
                    <td><strong>{{ $sumTotalGuests }}</strong></td>
                    <td></td>
                    <td><strong>{{ $sumTotalAmount  }}</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- Footer with page info -->
        <div style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 11px; color: #888; text-align: center;">
            <span>This is a system-generated document</span>
        </div>
    </div>

</body>

</html>