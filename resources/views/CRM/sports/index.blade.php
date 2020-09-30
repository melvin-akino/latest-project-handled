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
        <li class="active">Sports</li>
    </ol>
@endsection
@section('content')
    <div class="row">
        <table class="table table-striped" id="SportTable">
        <thead>
            <tr>
                <th colspan="5"><button class='add-modal btn btn-info'><span class='glyphicon glyphicon-add'></span> Add</button></th>
            </tr>
            <tr>
                <th class="text-left">Name</th>
                <th class="text-left">Details</th>
                <th class="text-left">Priority</th>
                <th class="text-left">Enabled</th>
                <th class="text-left">Options</th>
            </tr>
        </thead>
        </table>
    </div>
    @include('CRM.sports.manage')
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
            table = $('#SportTable').DataTable( {
                paging:   false,
                info:     false,
                searching: false,
                ordering: false,
                ajax: function (data, callback, settings) {
                    $.ajax({
                        url: "sports/list",
                    }).then ( function(json) {
                        callback(json);            
                    });
                },
                pageLength: 50,
                columns: [
                    { "data": "sport" },
                    { "data": "details" },
                    { "data": "priority" },
                    { 
                        "data": "is_enabled",
                        render : function (data, type, row) {
                            var renderIcon;
                            if (data === true) {
                                renderIcon = "<span class='glyphicon glyphicon-ok'></span>"
                            }
                            else {
                                renderIcon = "<span class='glyphicon glyphicon-remove'></span>"
                            }

                            return renderIcon;
                        },
                        "orderable": false

                    },
                    { 
                        "data": null, 
                        "defaultContent": "<button class='edit-modal btn btn-info'><span class='glyphicon glyphicon-edit'></span> Edit</button>",
                        "orderable": false 
                    }
                ],
                initComplete: function () {
                  init = false;
                },
                createdRow: function ( row, data, index ) {
                    //assign the provider id into the row
                    $(row).attr('id', 'sport-id-'+data.id);
                    $(row).addClass('row-sortable');
                },
                rowCallback: function ( row, data, index ) {

                }
            });

            $('#SportTable tbody').on('click', 'button.edit-modal', function () {
                var tr = $(this).closest('tr');
                var sportId = $(this).closest('tr').attr('id').replace('sport-id-', '');
                var row = table.row( tr );
                var rowData = row.data();

                var sportInfo = {};
                $.each(rowData, function( key, value ) {

                  sportInfo[key] = value;
                });


                var form = $('#form-manage-sport');
                form.attr('data-sport-id', sportId);
                form.find('input[name=sportId]').val(sportId);
                form.find('input[name=sport]').val(sportInfo['sport']);
                form.find('input[name=details]').val(sportInfo['details']);
                form.find("input[name=is_enabled][value=" + sportInfo['is_enabled'] + "]").prop('checked', true);

                $('#modal-manage-sport').modal('show');

            });

            $('#SportTable thead').on('click', 'button.add-modal', function () {
                $('#modal-manage-sport').modal('show');
            });

            

            $('#SportTable tbody').sortable({
                items: "tr",
                cursor: 'move',
                opacity: 0.6,
                update: function() {
                    updateSportPriority();
                }
            });
            
            function updateSportPriority() {
                var order = [];
                var token = $('meta[name="csrf-token"]').attr('content');

                $('tr.row-sortable').each(function(index,element) {
                    order.push({
                        id: $(this).attr('id').replace('sport-id-', ''),
                        position: index+1
                    });
                });

                $.ajax({
                    type: "POST", 
                    dataType: "json", 
                    url: "sports/sort",
                    data: {
                        order: order,
                        _token: token
                    },
                    success: function(response) {
                        if (response.data == "success") {
                            swal('Sport', 'Sport priority successfully updated.', response.data).then(() => {
                                table.ajax.reload();
                            });
                        } else {
                            console.log(response);
                        }
                    }
                });
            }
        });
    </script>
@endsection