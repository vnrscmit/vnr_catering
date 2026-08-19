@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<style>
    .section-header {
        font-size: 16px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }

    .section-header i {
        margin-right: 10px;
        color: #1A8C39;
    }

    .required-star {
        color: #dc3545;
        margin-left: 3px;
    }

    .text-muted-small {
        font-size: 12px;
        color: #6c757d;
    }

    .divider {
        border-top: 1px solid #e9ecef;
        margin: 20px 0;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-badge.draft {
        background: #fff3cd;
        color: #856404;
    }

    .status-badge.submitted {
        background: #d4edda;
        color: #155724;
    }

    .status-badge.cancelled {
        background: #f8d7da;
        color: #721c24;
    }

    .info-box {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        border-left: 4px solid #1A8C39;
    }

    .info-box label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }

    .info-box .value {
        color: #212529;
        font-size: 16px;
        font-weight: 500;
    }

    .user-detail-item {
        padding: 5px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .user-detail-item:last-child {
        border-bottom: none;
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            width: '100%',
            placeholder: 'Select an option',
            allowClear: true
        });

        // Calculate totals automatically
        function calculateTotals() {
            let totalDiets = parseFloat($('#total_diets').val()) || 0;
            let rate = parseFloat($('#rate').val()) || 0;
            let securityDeposit = parseFloat($('#security_deposit').val()) || 0;
            let adjustment = parseFloat($('#adjustment').val()) || 0;

            let totalNetChargable = totalDiets * rate;
            let netSettlementCharges = totalNetChargable - securityDeposit + adjustment;

            $('#total_net_chargable').val(totalNetChargable.toFixed(2));
            $('#net_settlement_charges').val(netSettlementCharges.toFixed(2));
        }

        // Trigger calculation on input change
        $('#total_diets, #rate, #security_deposit, #adjustment').on('input', function() {
            calculateTotals();
        });


        // Toast notification function
        function showToast(type, message) {
            alert(message);
        }

        // Auto-calculate on page load
        calculateTotals();
    });
</script>
@endpush

@section('title', 'Show Individual Settlement')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
        <div class="card">
            @include('partials.message-bag')

            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-eye me-2"></i> Show Individual Settlement
                    </h5>
                    <div>
                        <a href="{{ route('bill-generate.individual') }}" class="btn btn-secondary btn-sm ms-2">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form action="{{ route('bill-generate.individual.update', $bill->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Bill Information -->
                    <div class="info-box">
                        <h6 class="section-header">
                            <i class="fas fa-file"></i> Bill Details
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <label>Bill Number</label>
                                <div class="value">{{ $bill->bill_no }}</div>
                            </div>
                            <div class="col-md-4">
                                <label>Bill Date</label>
                                <div class="value">{{ date('d-m-Y', strtotime($bill->bill_date)) }}</div>
                            </div>
                            <div class="col-md-4">
                                <label>Generated Month</label>
                                <div class="value">{{ date('F Y', strtotime($bill->generate_month . '-01')) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- User & Department Information -->
                    <div class="info-box">
                        <h6 class="section-header">
                            <i class="fas fa-user"></i> User Details
                        </h6>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label>Employee Name</label>
                                <div class="value">{{ $bill->user->first_name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Role</label>
                                <div class="value">{{ $bill->user->role ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Email</label>
                                <div class="value">{{ $bill->user->email ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Phone</label>
                                <div class="value">{{ $bill->user->mobile ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Department</label>
                                <div class="value">{{ $bill->user->department->name ?? 'N/A' }}</div>
                            </div>
                        </div>

                    </div>

                    <div class="divider"></div>

                    <!-- Settlement Details -->
                     <div class="info-box">
                        <h6 class="section-header">
                            <i class="fas fa-calculator"></i> Settlement Details
                        </h6>
                        <div class="row">
                            <!-- Settlement Month -->
                            <div class="col-md-4 mb-3">
                                <label for="settlement_month">
                                    Settlement Month <span class="text-danger">*</span>
                                </label>
                                <input type="month"
                                    class="form-control @error('settlement_month') is-invalid @enderror"
                                    id="settlement_month"
                                    name="settlement_month"
                                    value="{{ old('settlement_month', $bill->generate_month) }}"
                                    required readonly>
                                @error('settlement_month')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Settlement Date -->
                            <div class="col-md-4 mb-3">
                                <label for="settlement_date">
                                    Settlement Date <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                    class="form-control @error('settlement_date') is-invalid @enderror"
                                    id="settlement_date"
                                    name="settlement_date"
                                    value="{{ old('settlement_date', $bill->bill_date) }}"
                                    required readonly>
                                @error('settlement_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Current Outstanding -->
                            <div class="col-md-4 mb-3">
                                <label for="currentOutstanding">
                                    Current Outstanding <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number"
                                        step="0.01"
                                        min="0"
                                        id="currentOutstanding"
                                        name="current_outstanding"
                                        class="form-control @error('current_outstanding') is-invalid @enderror"
                                        value="{{ old('current_outstanding', $bill->current_outstanding ?? 0) }}"
                                        required readonly>
                                </div>
                                @error('current_outstanding')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Total Diets -->
                            <div class="col-md-4 mb-3">
                                <label for="total_diets">
                                    Total Diets <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                    class="form-control @error('total_diets') is-invalid @enderror"
                                    id="total_diets"
                                    name="total_diets"
                                    value="{{ old('total_diets', $bill->total_diets) }}"
                                    step="0.01"
                                    min="0"
                                    required readonly>
                                <small class="text-muted-small">Total diets in current month</small>
                                @error('total_diets')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Rate -->
                            <div class="col-md-4 mb-3">
                                <label for="rate">
                                    Rate <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number"
                                        class="form-control @error('rate') is-invalid @enderror"
                                        id="rate"
                                        name="rate"
                                        value="{{ old('rate', $bill->per_diet_calculation) }}"
                                        step="0.01"
                                        min="0"
                                        required readonly>
                                </div>
                                <small class="text-muted-small">Reference Rate from previous month</small>
                                @error('rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Total Net Chargeable -->
                            <div class="col-md-4 mb-3">
                                <label for="total_net_chargable">
                                    Total Diet Charges Due <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="text"
                                        class="form-control @error('total_net_chargable') is-invalid @enderror"
                                        id="total_net_chargable"
                                        name="total_net_chargable"
                                        value="{{ old('total_net_chargable', $billDetail->bill_amount ?? 0) }}"
                                        readonly
                                        required>
                                </div>
                                <small class="text-muted-small">Auto-calculated (Diets × Rate)</small>
                                @error('total_net_chargable')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Security Deposit -->
                            <div class="col-md-4 mb-3">
                                <label for="security_deposit">
                                    Security Deposit <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number"
                                        class="form-control @error('security_deposit') is-invalid @enderror"
                                        id="security_deposit"
                                        name="security_deposit"
                                        value="{{ old('security_deposit', $billDetail->security_amount ?? 0) }}"
                                        step="0.01"
                                        min="0"
                                        required readonly>
                                </div>
                                @error('security_deposit')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>



                            <!-- Net Settlement Charges -->
                            <div class="col-md-4 mb-3">
                                <label for="net_settlement_charges">
                                    Net Settlement Charges <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="text"
                                        class="form-control @error('net_settlement_charges') is-invalid @enderror"
                                        id="net_settlement_charges"
                                        name="net_settlement_charges"
                                        value="{{ old('net_settlement_charges', $billDetail->settlement_amount ?? 0) }}"
                                        readonly
                                        required readonly>
                                </div>
                                <small class="text-muted-small">Total Net Chargable - Security Deposit + Adjustment</small>
                                @error('net_settlement_charges')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="organization-card show">
                        <h6 class="section-header">
                            <i class="fas fa-sticky-note"></i> Additional Notes
                        </h6>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="notes">Notes</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror"
                                    id="notes"
                                    name="notes"
                                    rows="3"
                                    placeholder="Enter any additional notes or remarks" readonly>{{ old('remarks', $bill->remarks ?? '') }}</textarea>
                                @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end mt-3">
                        <div class="mb-3">
                            <a href="{{ route('bill-generate.individual') }}"
                                class="btn btn-secondary"
                                style="width: 130px; height: 42px; border-radius: 6px; font-weight: 600;">
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