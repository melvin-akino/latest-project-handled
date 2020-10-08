@extends('CRM.layouts.dashboard')

@section('style')
    <style>
        canvas {
            -moz-user-select   : none;
            -webkit-user-select: none;
            -ms-user-select    : none;
        }

        .chart {
            height: 360px;
        }

        .ellipted {
            max-width    : 100px !important;
            white-space  : nowrap;
            text-overflow: ellipsis;
            overflow     : hidden;
        }

        .btn-link {
            padding: 0;
        }
        div.dataTables_wrapper {
            width: 90%;
            margin: 0 auto;
        }
        td.details-control {
            background: url('{{url('/crm-images/details_open.png')}}') no-repeat center center;
            cursor: pointer;
        }
        tr.shown td.details-control {
            background: url('{{url('/crm-images/details_close.png')}}') no-repeat center center;
        }
    </style>
@endsection
@section('breadcrumb')
    <ol class="breadcrumb">
        <li><i class="fa fa-dashboard"></i> Home</li>
        <li class="active">Unsettled Transactions</li>
    </ol>
@endsection
@section('content')
    <div class="row">
        <table class="table table-striped" id="UnsettledTable">
        <thead>
            <tr>
                <th class="text-left">BET ID</th>
                <th class="text-left">POST DATE</th>
                <th class="text-left">BET INFO</th>
                <th class="text-left">PROVIDER</th>
                <th class="text-left">STAKE</th>
                <th class="text-left">PRICE</th>
                <th class="text-left">TO WIN</th>
            </tr>
        </thead>
        </table>
    </div>
    @include('CRM.transactions.generate_settlement')
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css") }}">
@endsection

@section('scripts')

    <!-- DataTables -->  
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net/js/jquery.dataTables.min.js") }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/jquery-ui/ui/core.js") }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/jquery-ui/ui/widget.js") }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/jquery-ui/ui/mouse.js") }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/jquery-ui/ui/sortable.js") }}"></script>
    <script src="{{ asset("CRM/Capital7-1.0.0/js/form-validation.js") }}"></script>
    <script type="text/javascript" >
        var table;
        $(document).ready(function() {
            table = $('#UnsettledTable').DataTable({
                ajax: function (data, callback, settings) {
                    $.ajax({
                        url: "transactions/unsettled",
                    }).then ( function(json) {
                        callback(json);            
                    });
                },
                pageLength: 10,
                columns: [
                    { "data": "error" },
                    { 
                        "data": null, 
                        "defaultContent": "<button class='edit btn btn-info'>Edit</button>",
                        "orderable": false 
                    }
                ],
                initComplete: function () {
                  init = false;
                },
                createdRow: function ( row, data, index ) {
                    //assign the provider id into the row
                    $(row).attr('id', 'orderId-'+data.id);
                },
                rowCallback: function ( row, data, index ) {

                }
            });


            $('#UnsettledTable tbody').on('click', 'button.edit', function () {
                var tr = $(this).closest('tr');
                var orderId = $(this).closest('tr').attr('id').replace('orderId-', '');
                var row = table.row( tr );
                var rowData = row.data();

                var orderInfo = {};
                $.each(rowData, function( key, value ) {

                  orderInfo[key] = value;
                });


                var form = $('#form-generate-settlement');
                form.attr('data-order-id', orderId);
                form.find('input[name=orderId]').val(orderId);
                form.find('input[name=error]').val(errorInfo['error']);


                $('#modal-generate-settlement').modal('show');

            });

        });
    </script>
@endsection