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
        <li class="active">Error Messages</li>
    </ol>
@endsection
@section('content')
    <div class="row">
        <table class="table table-striped" id="ErrorMessagesTable">
        <thead>
            <tr>
                <th colspan="6"><button class='add-modal btn btn-info'><span class='glyphicon glyphicon-add'></span> Add</button></th>
            </tr>
            <tr>
                <th class="text-left">Error Messages</th>
                <th></th>
            </tr>
        </thead>
        </table>
    </div>
    @include('CRM.error_messages.manage')
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
            table = $('#ErrorMessagesTable').DataTable({
                ajax: function (data, callback, settings) {
                    $.ajax({
                        url: "error_messages/list",
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
                    $(row).attr('id', 'errorMessageId-'+data.id);
                },
                rowCallback: function ( row, data, index ) {

                }
            });

            $('#ErrorMessagesTable thead').on('click', 'button.add-modal', function () {
                $('#modal-manage-error-message').modal('show');
                $('#form-manage-error-message').find('input[name=errorMessageId]').val('');
            });

            $('#ErrorMessagesTable tbody').on('click', 'button.edit', function () {
                var tr = $(this).closest('tr');
                var errorMessageId = $(this).closest('tr').attr('id').replace('errorMessageId-', '');
                var row = table.row( tr );
                var rowData = row.data();

                var errorInfo = {};
                $.each(rowData, function( key, value ) {

                  errorInfo[key] = value;
                });


                var form = $('#form-manage-error-message');
                form.attr('data-error-message-id', errorMessageId);
                form.find('input[name=errorMessageId]').val(errorMessageId);
                form.find('input[name=error]').val(errorInfo['error']);


                $('#modal-manage-error-message').modal('show');

            });

        });
    </script>
@endsection