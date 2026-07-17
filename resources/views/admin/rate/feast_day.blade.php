@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {

        $(document).on('click', '.addRowBtn', function() {

            let row = `
        <tr>
            <td>
                <input type="date"
                       name="feast_date[]"
                       class="form-control"
                       required>
            </td>

            <td>
                <input type="number"
                       step="0.01"
                       min="0"
                       name="member_rate[]"
                       class="form-control"
                       placeholder="Member Rate"
                       value="0"
                       required>
            </td>

            <td>
                <input type="number"
                       step="0.01"
                       min="0"
                       name="non_member_rate[]"
                       class="form-control"
                       placeholder="Non Member Rate"
                       value="0"
                       required>
            </td>

            <td>
                <input type="number"
                       step="0.01"
                       min="0"
                       name="guest_rate[]"
                       class="form-control"
                       placeholder="Guest Rate"
                       value="0"
                       required>
            </td>

            <td width="150">
                <button type="button"
                        class="btn btn-primary btn-sm addRowBtn">
                    <i class="fa fa-plus"></i>
                </button>

                <button type="button"
                        class="btn btn-danger btn-sm removeRow">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>`;

            $('#feastRateTable tbody').append(row);
        });

        $(document).on('click', '.removeRow', function() {

            if ($('#feastRateTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                alert('At least one row is required.');
            }

        });

    });
</script>
@endpush

@section('content')

<div class="main-panel">

    <div class="content-wrapper">
               @include('partials.message-bag')

        <div class="card">

            <div class="card-header">
                <h4 class="mb-0">Add Feast Day Rates</h4>
            </div>

            <div class="card-body">

                <form action="{{ route('feast-day-rates.store') }}" method="POST">
                    @csrf

                    {{-- If Rate Master Id is required --}}
               <input type="hidden" name="rate_master_id" value="{{ $rateMaster->id }}">

                    <table class="table table-bordered" id="feastRateTable">

                        <thead class="table-light">
                            <tr>
                                <th width="20%">Feast Date</th>
                                <th>Member Rate</th>
                                <th>Non Member Rate</th>
                                <th>Guest Rate</th>
                                <th width="150">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>
                                <td>
                                    <input type="date"
                                        name="feast_date[]"
                                        class="form-control"
                                        min="{{ $minDate->format('Y-m-d') }}"
                                        max="{{ $maxDate->format('Y-m-d') }}"
                                        required>
                                </td>

                                <td>
                                    <input type="number"
                                        step="0.01"
                                        min="0"
                                        name="member_rate[]"
                                        class="form-control"
                                        placeholder="Member Rate"
                                        value="0"
                                        required>
                                </td>

                                <td>
                                    <input type="number"
                                        step="0.01"
                                        min="0"
                                        name="non_member_rate[]"
                                        class="form-control"
                                        placeholder="Non Member Rate"
                                        value="0"
                                        required>
                                </td>

                                <td>
                                    <input type="number"
                                        step="0.01"
                                        min="0"
                                        name="guest_rate[]"
                                        class="form-control"
                                        placeholder="Guest Rate"
                                        value="0"
                                        required>
                                </td>

                                <td>
                                    <button type="button"
                                        class="btn btn-primary btn-sm addRowBtn">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </td>

                            </tr>

                        </tbody>

                    </table>


                    <!-- <div class="text-end mt-3">

                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Submit
                        </button>

                        <a href="{{ url()->previous() }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>

                    </div> -->

                    <div class="d-flex justify-content-end  mt-2">
                        <div class="mb-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Submit
                            </button>
                            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

@endsection