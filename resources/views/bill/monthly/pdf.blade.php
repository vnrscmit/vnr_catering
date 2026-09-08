<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Monthly Bill</title>
    <style>
        @page {
            margin: 20px;
            margin-header: 120px;
            margin-footer: 50px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }


        .title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 30px;
            /* text-transform: uppercase; */
        }

        .subtitle {
            font-size: 16px;
        }

        .header {
            /* border-bottom: 1px solid #000; */
            margin-bottom: 6px;
            padding-bottom: 6px;
        }

        /* ===== FIX: Header repeating ===== */
        .pdf-header {
            position: running(header);
        }

        /* ===== FIX: Content margin to avoid overlap ===== */
        .page-content {
            margin-top: 120px;
        }

        /* ===== FIX: First page margin ===== */
        @page :first {
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <!-- Items table (bottom part) -->
    <table style="margin-top: 5px;">
        <thead>
            <!-- HEADER - Will repeat on every page -->
            <tr>
                <td colspan="9" style="border: none; padding: 10px 0;">
                    <table style="margin-bottom: 5px; border: none; width: 100%; table-layout: fixed; border-collapse: collapse;">
                        <tr>
                            <td style="border: none; width: 20%; vertical-align: middle; text-align: left; padding: 5px 0;">
                                <img src="{{ public_path('assets/images/dashboard_logo_vnr.png') }}"
                                    style="max-height: 100px; width: auto; display: inline-block;">
                            </td>
                            <td style="border: none; width: 56%; vertical-align: middle; text-align: center; padding: 5px 0;">
                                <div style="font-size: 20px; font-weight: bold;">
                                    Canteen Billing Statement
                                </div>
                                <div style="font-size: 16px; margin-top: 5px;">
                                    Month - {{ \Carbon\Carbon::parse($bill->generate_month)->format('F Y') }}
                                </div>
                            </td>
                            <td style="border: none; width: 26%; vertical-align: middle; text-align: left; padding: 5px 0;">
                                <span style="font-size: 12px; color: #666;">Bill Number: {{ $bill->bill_no }}</span><br>
                                <span style="font-size: 12px; color: #666;">Bill Date: {{ \Carbon\Carbon::parse($bill->generate_date)->format('d-m-Y') }}</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <!-- TABLE HEADERS -->
            <tr style="font-weight: bold; background-color: #f5f5f5;">
                <th style="width:5%;">#</th>
                <th style="width:12%;">Name</th>
                <th style="width:12%;">Type</th>
                <th style="width:12%;">No. of Day</th>
                <th style="width:12%;">Rate</th>
                <th style="width:12%;">Amount</th>
                <th style="width:12%;">Bal. of Last Month</th>
                <th style="width:10%;">Total Amount</th>
                <th style="width:13%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($billDetails as $data)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>{{ $data->user->first_name ?? '' }}</td>
                <td>{{ $data->role ?? '' }}</td>
                <td>{{ $data->user_diets ?? 0 }}</td>
                <td>{{ $data->rate_per_diet ?? 0 }}</td>
                <td>{{ $data->bill_amount ?? 0 }}</td>
                <td>{{ $data->pre_balance ?? 0 }}</td>
                <td>{{ $data->balance ?? 0 }}</td>
                <td class="text-right"></td>
            </tr>
            @endforeach

            <!-- Total row -->
            <tr style="font-weight: bold; background-color: #f5f5f5;">
                <td class="text-left" style="padding-right: 10px;" colspan="2">Total</td>
                <td class="text-left"></td>
                <td class="text-left">{{ $billDetails->sum('user_diets') ?? 0 }}</td>
                <td class="text-left"></td>
                <td class="text-left">{{ $billDetails->sum('bill_amount') ?? 0 }}</td>
                <td class="text-left"></td>
                <td class="text-left">{{ $billDetails->sum('balance') ?? 0 }}</td>
                <td class="text-left"></td>
            </tr>

            <tr>
                <td colspan="9" style="font-size: 16px; line-height: 1.6; padding: 35px 10px; vertical-align: top;">
                    Note: Please make the payment in cash only and ensure it is completed by the <strong>10th of every month.</strong>
                </td>
            </tr>
        </tbody>
    </table>

</body>

</html>