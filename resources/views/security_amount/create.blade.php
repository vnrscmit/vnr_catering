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
@endpush

@section('title', 'Security Amount')
@section('content')

<div class="main-panel">
    <div class="content-wrapper">
          @include('partials.message-bag')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Add Security Amount - {{ auth()->user()->location->name ?? 'N/A' }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('security-amount.store') }}" method="POST">
                    @csrf

                    <!-- Row 1: Transaction Date, Select User, Existing Security Amount -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="date" class="form-label">Transaction Date <span class="text-danger">*</span></label>
                            <input type="date"
                                class="form-control @error('date') is-invalid @enderror"
                                id="date"
                                name="date"
                                value="{{ old('date', date('Y-m-d')) }}"
                                required readonly>
                            @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="user_id" class="form-label">Select User <span class="text-danger">*</span></label>
                            <select class="form-control @error('user_id') is-invalid @enderror"
                                id="user_id"
                                name="user_id"
                                required>
                                <option value="">Select User</option>
                                @foreach($users ?? [] as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->first_name ?? '' }}
                                </option>
                                @endforeach
                            </select>
                            @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="existing_security" class="form-label">Existing Security Amount</label>
                            <div class="input-group">
                                <input type="text"
                                    class="form-control"
                                    id="existing_security"
                                    name="existing_security"
                                    value="0"
                                    readonly>
                            </div>
                            <small class="text-muted">Auto-fills when user selected</small>
                        </div>
                    </div>

                    <!-- Row 2: Additional Security Amount, Total Security Amount, Mode of Collection -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="amount" class="form-label">Additional Security Amount <span class="text-danger">*</span></label>
                            <div class="input-group">

                                <input type="number"
                                    class="form-control @error('amount') is-invalid @enderror"
                                    id="amount"
                                    name="amount"
                                    value="{{ old('amount') }}"
                                    step="0.01"
                                    min="0"
                                    placeholder="Enter additional amount"
                                    required>
                            </div>
                            @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="total_security" class="form-label">Total Security Amount</label>
                            <div class="input-group">

                                <input type="text"
                                    class="form-control"
                                    id="total_security"
                                    name="total_security_amount"
                                    value=""
                                    readonly>
                            </div>
                            <small class="text-muted">Existing + Additional amount</small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Mode of Collection <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-3 flex-wrap" style="padding-top: 6px;">
                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="mode_of_collection"
                                        id="mode_cash"
                                        value="Cash"
                                        checked>
                                    <label class="form-check-label" for="mode_cash">
                                        Cash
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="mode_of_collection"
                                        id="mode_upi"
                                        value="UPI">
                                    <label class="form-check-label" for="mode_upi">
                                        UPI
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input"
                                        type="radio"
                                        name="mode_of_collection"
                                        id="mode_other"
                                        value="Other">
                                    <label class="form-check-label" for="mode_other">
                                        Other
                                    </label>
                                </div>
                            </div>
                            @error('mode_of_collection')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" name="transaction_id" value="{{ $transactionId ?? '' }}">
                    <input type="hidden" name="status" value="1">

                    <!-- Buttons -->
                    <div class="row">
                        <div class="col-12 d-flex justify-content-end">
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Submit
                                </button>
                                <a href="{{ route('security-amount.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Wait for DOM to be fully loaded and jQuery to be available
    document.addEventListener('DOMContentLoaded', function() {
        // Check if jQuery is loaded
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded!');
            return;
        }

        // Use jQuery with noConflict if needed
        (function($) {
            $(document).ready(function() {
                // Auto-fill existing security amount when user is selected
                $('#user_id').on('change', function() {
                    var userId = $(this).val();
                    if (userId) {
                        // Show loading state
                        $('#existing_security').val('Loading...');

                        $.ajax({
                            url: '{{ route("security-amount.user-total", ["userId" => ":userId"]) }}'.replace(':userId', userId),
                            type: 'GET',
                            dataType: 'json',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                console.log('AJAX Response:', response);
                                if (response.success) {
                                    var existingAmount = response.data.total_security_amount || 0;
                                    $('#existing_security').val(existingAmount);
                                    calculateTotal();
                                } else {
                                    $('#existing_security').val('0');
                                    calculateTotal();
                                    if (response.message) {
                                        console.error(response.message);
                                    }
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX Error:', error);
                                $('#existing_security').val('0');
                                calculateTotal();
                            }
                        });
                    } else {
                        $('#existing_security').val('0');
                        calculateTotal();
                    }
                });

                // Calculate total when additional amount changes
                $('#amount').on('keyup change input', function() {
                    calculateTotal();
                });

                // Function to calculate total
                function calculateTotal() {
                    var existing = parseFloat($('#existing_security').val()) || 0;
                    var additional = parseFloat($('#amount').val()) || 0;
                    var total = existing + additional;
                    $('#total_security').val(total);
                }

                // Initial calculation
                calculateTotal();
            });
        })(jQuery);
    });
</script>

<style>
    .input-group-text {
        background-color: #f8f9fa;
    }

    .form-check-label i {
        margin-right: 5px;
    }

    .form-check {
        margin-bottom: 5px;
    }

    input[readonly] {
        background-color: #f8f9fa;
        cursor: not-allowed;
    }
</style>

@endsection