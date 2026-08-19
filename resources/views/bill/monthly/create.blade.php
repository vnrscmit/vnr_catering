@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
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

        function calculateBill() {

            let totalExpense = parseFloat($('#total_expenses').val()) || 0;
            let guestExpense = parseFloat($('#guest_expenses').val()) || 0;
            let nonMemberExpense = parseFloat($('#non_member_expenses').val()) || 0;
            let individualExpense = parseFloat($('#individual_expenses').val()) || 0;
            let netChargeableDiet = parseFloat($('#net_chargeable_diet').val()) || 0;

            // Net Monthly Expense
            let netExpense = totalExpense - (guestExpense + individualExpense + nonMemberExpense);

            if (netExpense < 0) {
                netExpense = 0;
            }

            $('#net_monthly_expenses').val(netExpense.toFixed(0));

            // Per Diet Calculation
            let perDiet = 0;

            if (netChargeableDiet > 0) {
                perDiet = (netExpense / netChargeableDiet).toFixed(2);
                perDietManual = Math.ceil(netExpense / netChargeableDiet);
            }

            $('#per_diet_calculation').val(perDiet);
            $('#per_diet_calculation_manual').val(perDietManual);
        }

        $('#total_expenses').on('input keyup change', function() {
            calculateBill();
        });

    });
</script>
@endpush

@section('title','Generate Bill')

@section('content')

<div class="main-panel">
    <div class="content-wrapper">

        @include('partials.message-bag')

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Generate Bill - Group Settlement</h5>
            </div>

            <div class="card">

                <div class="card-body">

                    <form action="{{ route('bill-generate.monthly.store') }}" method="POST">

                        @csrf

                        <div class="row">

                            <div class="col-md-4">
                                <label class="form-label">
                                    Month <span class="text-danger">*</span>
                                </label>

                                <input type="month"
                                    name="generate_month"
                                    class="form-control"
                                    value="{{ old('generate_month', now()->subMonth()->format('Y-m')) }}"
                                    readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    Bill Date <span class="text-danger">*</span>
                                </label>

                                <input type="date"
                                    name="bill_date"
                                    class="form-control"
                                    value="{{ old('bill_date', now()->format('Y-m-d')) }}" readonly>
                            </div>

                        </div>

                        <hr>

                        <div class="row">

                            <div class="col-md-6">

                                <table class="table table-bordered">

                                    <tr>
                                        <th>Total Diet</th>
                                        <td class="text-end">
                                            <input type="text"
                                                name="total_diets"
                                                class="form-control text-end"
                                                value="{{ old('total_diets', $totalDiets) }}"
                                                readonly>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Individual Settlement Diet</th>
                                        <td class="text-end">
                                            <input type="text"
                                                name="individual_settlement_diet"
                                                class="form-control text-end"
                                                value="{{ old('individual_settlement_diet', $individualSettlementDiet) }}"
                                                readonly>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Guest Diet</th>
                                        <td class="text-end">
                                            <input type="text"
                                                name="guest_diet"
                                                class="form-control text-end"
                                                value="{{ old('guest_diet', $guestDiet) }}"
                                                readonly>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Non Member Diet</th>
                                        <td class="text-end">
                                            <input type="text"
                                                name="non_member_diet"
                                                class="form-control text-end"
                                                value="{{ old('non_member_diet', $nonMemberDiet) }}"
                                                readonly>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>President Diet</th>
                                        <td class="text-end">
                                            <input type="text"
                                                name="president_diet"
                                                class="form-control text-end"
                                                value="{{ old('president_diet', $presidentDiet) }}"
                                                readonly>
                                        </td>
                                    </tr>



                                    <tr class="table-success">
                                        <th>Net Chargeable Diet</th>
                                        <td class="text-end">
                                            <input type="text" id="net_chargeable_diet"
                                                name="net_chargeable_diet"
                                                class="form-control text-end fw-bold"
                                                value="{{ old('net_chargeable_diet', $netChargeableDiet) }}"
                                                readonly>
                                        </td>
                                    </tr>

                                </table>

                            </div>


                            <div class="col-md-6">
                                <table class="table table-bordered">

                                    <tr>
                                        <th>Total Expense <span class="text-danger">*</span></th>
                                        <td class="text-end">
                                            <input type="text"
                                                name="total_expenses"
                                                class="form-control text-start"
                                                inputmode="numeric" id="total_expenses"
                                                pattern="[0-9]*"
                                                maxlength="7" required>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Individual Settlement Charges</th>
                                        <td class="text-end">
                                            <input type="text" id="individual_expenses" name="individual_expenses" class="form-control text-start" value="{{ $individualExpense }}" readonly>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Guest Charges</th>
                                        <td class="text-end">
                                            <input type="text" id="guest_expenses" name="guest_expenses" class="form-control text-start" value="{{ $guestExpense }}" readonly>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Non Member Charges</th>
                                        <td class="text-end">
                                            <input type="text" id="non_member_expenses" name="non_member_expenses" class="form-control text-start" value="{{ $nonMemberExpenses }}" readonly>
                                        </td>
                                    </tr>

                                    <tr class="table-warning">
                                        <th>Net Monthly Expense</th>
                                        <td class="text-end">
                                            <input type="text" id="net_monthly_expenses" name="net_monthly_expenses" value="0" class="form-control text-start" value="0" readonly>
                                        </td>
                                    </tr>

                                    <tr class="table-primary">
                                        <th>Per Diet Calculation Rate</th>
                                        <td class="text-end">
                                            <input type="text" id="per_diet_calculation" name="per_diet_calculation" class="form-control text-start" value="0" readonly>
                                        </td>
                                    </tr>

                                    <tr class="table-success">
                                        <th>Enter Per Diet Rate</th>
                                        <td class="text-end">
                                            <input type="text" id="per_diet_calculation_manual" name="per_diet_calculation_manual" class="form-control text-start" value="0" min="1">
                                        </td>
                                    </tr>

                                </table>

                            </div>

                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Preview & Submit
                                </button>
                                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back
                                </a>
                            </div>
                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>
</div>

@endsection