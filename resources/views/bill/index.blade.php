@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="/admin_resources/vendors/typicons.font/font/typicons.css">
<link rel="stylesheet" href="/admin_resources/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="/admin_resources/css/vertical-layout-light/style.css">

<link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
@endpush

@push('scripts')
<script src="/admin_resources/vendors/js/vendor.bundle.base.js"></script>
<script src="/admin_resources/js/off-canvas.js"></script>
<script src="/admin_resources/js/hoverable-collapse.js"></script>
<script src="/admin_resources/js/template.js"></script>
<script src="/admin_resources/js/settings.js"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<script>
$(function () {

    $('.data-table').DataTable({

        processing: true,
        serverSide: true,

        ajax: "{{ route('bill-generate.index') }}",

        columns: [
            {
                data:'DT_RowIndex',
                name:'DT_RowIndex',
                searchable:false,
                orderable:false
            },
            {
                data:'bill_no',
                name:'bill_no'
            },
            {
                data:'generate_month',
                name:'generate_month'
            },
            {
                data:'bill_date',
                name:'bill_date'
            },
            {
                data:'total_diets',
                name:'total_diets'
            },
            {
                data:'net_monthly_expenses',
                name:'net_monthly_expenses'
            },
            {
                data:'per_diet_calculation',
                name:'per_diet_calculation'
            },
            {
                data:'status',
                name:'status'
            },
            {
                data:'action',
                name:'action',
                searchable:false,
                orderable:false
            }
        ],

        pageLength:10,

        dom:'lBfrtip',

        buttons:[
            'excel',
            'pdf',
            'print'
        ]

    });

});
</script>
@endpush

@section('title','Bill Generation')

@section('content')

<div class="main-panel">

    <div class="content-wrapper">

        @include('partials.message-bag')

        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">

                <h5 class="card-title mb-0">
                    Bill Generation -
                    {{ auth()->user()->location->name ?? '' }}
                </h5>

                <a href="{{ route('bill-generate.create') }}"
                    class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i>
                    Generate Bill
                </a>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-striped data-table">

                        <thead class="table-light">

                        <tr>

                            <th>#</th>
                            <th>Month</th>
                            <th>Bill Date</th>
                            <th>Total Diets</th>
                            <th>Total Expenses</th>
                            <th>Rate/Diet</th>
                            <th>Status</th>
                            <th width="150">Action</th>

                        </tr>

                        </thead>

                    </table>

                </div>

            </div>

        </div>

    </div>

    @include('partials.admin.footer')

</div>

@endsection