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
        <li class="active">Providers</li>
    </ol>
@endsection
@section('content')
    <div class="row">
        <table class="table table-striped" id="ProviderTable">
        <thead>
            <tr>
                <th colspan="6"><button class='add-modal btn btn-info'><span class='glyphicon glyphicon-add'></span> Add</button></th>
            </tr>
            <tr>
                <th></th>
                <th class="text-left">Name</th>
                <th class="text-left">Alias</th>
                <th class="text-left">Percentage</th>
                <th class="text-left">Priority</th>
                <th class="text-left">Enabled</th>
                <th class="text-left">Options</th>
            </tr>
        </thead>
        </table>
    </div>
    @include('CRM.providers.manage')
    @include('CRM.provider_accounts.manage')
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
        var providerList = [];
        var providerAccountList = [];
        var systemConfigurations = [];
        var table;
        var childTable;

        $(document).ready(function() {
            table = $('#ProviderTable').DataTable( {
                paging:   false,
                info:     false,
                searching: false,
                ordering: false,
                ajax: function (data, callback, settings) {
                    $.ajax({
                        url: "providers/list",
                    }).then ( function(json) {
                        //construct the providers list
                        $.each(json.data,function(key, value) 
                        {
                            providerList[key] = {'id' : value['id'], 'name' : value['name'], 'alias' : value['alias']};
                        });
                        //get system configurations list
                        $.ajax({
                            url: "system_configurations/list",
                        }).then ( function(json) {
                            $.each(json.data,function(key, value) 
                            {
                                systemConfigurations[key] = { 'id' : value['type'].trim(), 'name' : value['type'].trim()};
                            });        
                        });
                        callback(json);            
                    });
                },
                pageLength: 50,
                columns: [
                    {
                        "className":      'details-control',
                        "orderable":      false,
                        "data":           null,
                        "defaultContent": ''
                    },
                    { "data": "name" },
                    { "data": "alias" },
                    { "data": "percentage", "orderable": false },
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
                    $(row).attr('id', 'provider-id-'+data.id);
                    $(row).addClass('row-sortable');
                    if (data.extn === '') {
                      var td = $(row).find("td:first");
                      td.removeClass( 'details-control' );
                    }
                },
                rowCallback: function ( row, data, index ) {

                }
            });

            $('#ProviderTable tbody').on('click', 'button.edit-modal', function () {
                var tr = $(this).closest('tr');
                var providerId = $(this).closest('tr').attr('id').replace('provider-id-', '');
                var row = table.row( tr );
                var rowData = row.data();

                var providerInfo = {};
                $.each(rowData, function( key, value ) {

                  providerInfo[key] = value;
                });


                var form = $('#form-manage-provider');
                form.attr('data-provider-id', providerId);
                form.find('input[name=providerId]').val(providerId);
                form.find('input[name=name]').val(providerInfo['name']);
                form.find('input[name=alias]').val(providerInfo['alias']);
                form.find('input[name=percentage]').val(providerInfo['percentage']);
                form.find("input[name=is_enabled][value=" + providerInfo['is_enabled'] + "]").prop('checked', true);

                $('#modal-manage-provider').modal('show');

            });

            $('#ProviderTable thead').on('click', 'button.add-modal', function () {
                $('#modal-manage-provider').modal('show');
            });

            // Add event listener for opening and closing first level childdetails
            $('#ProviderTable tbody').on('click', 'td.details-control', function () {
                var tr = $(this).closest('tr');
                var providerId = $(this).closest('tr').attr('id').replace('provider-id-', '');
                var row = table.row( tr );
                var rowData = row.data();

                //get index to use for child table ID
                var index = row.index();
         
                if ( row.child.isShown() ) {
                    // This row is already open - close it
                    row.child.hide();
                    tr.removeClass('shown');
                }
                else {
                    // Open this row
                    row.child( 
                       '<table class="child_table table table-striped" id = "child_details' + index + '" cellpadding="5" cellspacing="0" border="0">'+
                       '<thead><tr><th colspan="8"><button class="add-pa-modal btn btn-info"><span class="glyphicon glyphicon-add"></span> Add</button></th></tr>'+
                       '<tr><th>Username</th><th>Password</th><th>Type</th><th>Percentage</th><th>Credits</th><th>Enabled</th><th>Idle</th><th>Options</th></tr>'+
                       '</thead><tbody></tbody></table>').show();
              
                    childTable = $('#child_details' + index).DataTable({
                        autoWidth : true,
                        ajax: function (data, callback, settings) {
                            $.ajax({
                                url: "provider_accounts/" + providerId, 
                            }).then ( function(json) {
                                var data = json.data;
                                var display = [];
                                if (data) {

                                    for (d = 0; d < data.length; d++) {
                                        if (data[d].position == rowData.position) {
                                            display.push(data[d]);
                                        }
                                    }                                       
                                }

                                providerAccountList = [];

                                $.each(json.data,function(key, value) 
                                {
                                    providerAccountList[key] = {'username' : value['username']};
                                });

                                callback({data: display});               
                            });
                        },
                        columns: [
                            { "data": "username" },
                            { "data": "password", "orderable": false },
                            { "data": "type" },
                            { "data": "percentage", "orderable": false },
                            { "data": "credits", "orderable": false },
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
                                "data": "is_idle", 
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
                                "defaultContent": "<button class='edit-pa-modal btn btn-info'><span class='glyphicon glyphicon-edit'></span> Edit</button> <button class='delete-pa-modal btn btn-danger'><span class='glyphicon glyphicon-remove'></span> Delete</button>",
                                "orderable": false 
                            }
                        ],
                        createdRow: function ( row, data, index ) {
                            //assign the provider id into the row
                            $(row).attr('id', 'provider-account-id-'+data.id);                          

                            if (data.extn === '') {
                              var td = $(row).find("td:first");
                              td.removeClass( 'details-control' );
                            }
                        },
                        destroy: true
                    });

                    //populate the 2 dropdowns with data
                    var providerLen = providerList.length;

                    $("#provider_id").empty();
                    for( var i = 0; i<providerLen; i++){
                        var id = providerList[i]['id'];
                        var name = providerList[i]['name'];
                        
                        $("#provider_id").append("<option value='"+id+"'>"+name+"</option>");

                    }
                    $("#provider_id").val(providerId);

                    var paTypeLen = systemConfigurations.length;

                    $("#account_type").empty();
                    for( var i = 0; i<paTypeLen; i++){
                        var id = systemConfigurations[i]['id'];
                        var name = systemConfigurations[i]['name'];
                        
                        $("#account_type").append("<option value='"+id+"'>"+name+"</option>");

                    }

                    //Clicking this will open the manage provider account interface
                    $('#child_details'+index+' thead').on('click', 'button.add-pa-modal', function () {
                        var form = $('#form-manage-provider-account');
                        form.find('input[name=providerAccountId]').val('');
                        form.find('input[name=username]').removeAttr("disabled");
                        form.find('select[name=provider_id]').removeAttr("disabled");
                        $('#modal-manage-provider-accounts').modal('show');
                    });

                    //Clicking this will open the manage provider interace with pre-set data from the databale
                    $('#child_details'+index+' tbody').on('click', 'button.edit-pa-modal', function () {
                        var pa_tr = $(this).closest('tr');
                        var providerAccountId = $(this).closest('tr').attr('id').replace('provider-account-id-', '');

                        var pa_is_enabled = ($(pa_tr).find('td:eq(5)').html().search('ok') !== -1) ? 1 : 0;
                        var is_idle = ($(pa_tr).find('td:eq(6)').html().search('ok') !== -1) ? 1 : 0;

                        var form = $('#form-manage-provider-account');
                        form.attr('data-provider-account-id', providerAccountId);
                        form.find('input[name=providerAccountId]').val(providerAccountId);
                        form.find('input[name=username]').val($(pa_tr).find('td:eq(0)').html().trim()).attr('disabled', 'disabled');
                        form.find('input[name=password]').val($(pa_tr).find('td:eq(1)').html().trim());
                        form.find('select[name=account_type]').val($(pa_tr).find('td:eq(2)').html().trim());
                        form.find('input[name=pa_percentage]').val($(pa_tr).find('td:eq(3)').html().trim());                        
                        form.find('select[name=provider_id]').val(providerId).attr('disabled', 'disabled');
                        form.find("input[name=pa_is_enabled][value=" + pa_is_enabled + "]").prop('checked', true);
                        form.find("input[name=is_idle][value=" + is_idle + "]").prop('checked', true);


                        $('#modal-manage-provider-accounts').modal('show');

                    });

                    //Clicking this will open the delete popup with confirmation - the method only perform softDelete
                    $('#child_details'+index+' tbody').on('click', 'button.delete-pa-modal', function () {
                        var pa_tr = $(this).closest('tr');
                        var providerAccountId = $(this).closest('tr').attr('id').replace('provider-account-id-', '');

                        swal({
                            title: 'Are you sure?',
                            text: "You won't be able to revert this!",
                            type: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes, delete it!'
                        })
                        .then((result) => {
                            if (result.value) {
                                $.get('provider_accounts/delete/'+providerAccountId, function (response) {
                                    if (response.data == 'success') {
                                        swal('Provider Account', 'Provider account successfully deleted', response.data).then(() => {
                                            childTable.ajax.reload();
                                        });
                                    }
                                });
                            }
                        });
                                                       
                    });

                    tr.addClass('shown');
                }          
            });

            $('#ProviderTable tbody').sortable({
                items: "tr",
                cursor: 'move',
                opacity: 0.6,
                update: function() {
                    updateProviderPriority();
                }
            });
            
            function updateProviderPriority() {
                var order = [];
                var token = $('meta[name="csrf-token"]').attr('content');

                $('tr.row-sortable').each(function(index,element) {
                    order.push({
                        id: $(this).attr('id').replace('provider-id-', ''),
                        position: index+1
                    });
                });

                $.ajax({
                    type: "POST", 
                    dataType: "json", 
                    url: "providers/sort",
                    data: {
                        order: order,
                        _token: token
                    },
                    success: function(response) {
                        if (response.data == "success") {
                            swal('Provider', 'Provider priority successfully updated.', response.data).then(() => {
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