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
            background: url('https://www.datatables.net/examples/resources/details_open.png') no-repeat center center;
            cursor: pointer;
        }
        tr.shown td.details-control {
            background: url('https://www.datatables.net/examples/resources/details_close.png') no-repeat center center;
        }


        td.details-control1 {
            background: url('https://www.datatables.net/examples/resources/details_open.png') no-repeat center center;
            cursor: pointer;
        }
        tr.shown td.details-control1 {
            background: url('https://www.datatables.net/examples/resources/details_close.png') no-repeat center center;
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
        <table class="table" id="ProviderTable">
        <thead>
            <tr>
                <th colspan="6"><button class='add-modal btn btn-info'><span class='glyphicon glyphicon-add'></span> Add</button></th>
            </tr>
            <tr>
                <th></th>
                <th class="text-left">Name</th>
                <th class="text-left">Alias</th>
                <th class="text-left">Percentage</th>
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
    <!-- DateRagePicker -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/bootstrap-daterangepicker/daterangepicker.css") }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css") }}">
@endsection

@section('scripts')
    <!-- ChartJS -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/chart.js/dist/Chart.min.js") }}"></script>
    <script src="{{ asset("CRM/Capital7-1.0.0/js/utils.js") }}"></script>
    <!-- MomentJS -->
    <script src="{{ asset("CRM/Capital7-1.0.0/plugins/moment/moment.min.js") }}"></script>
    <!-- DateRagePicker -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/bootstrap-daterangepicker/daterangepicker.js") }}"></script>
    <!-- DataTables -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net/js/jquery.dataTables.min.js") }}"></script>
    <script src="{{ asset("js/jquery.validate.min.js") }}"></script>
    <script type="text/javascript" >
        $(document).ready(function() {

            var providerList = [];
            var systemConfigurations = [];
            var childTable;

            var table = $('#ProviderTable').DataTable( {
                paging:   false,
                info:     false,
                searching: false,
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
                                systemConfigurations[key] = { 'id' : value['type'], 'name' : value['type']};
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
                    { "data": "percentage" },
                    { "data": "is_enabled" },
                    { 
                        "data": null, 
                        "defaultContent": "<button class='edit-modal btn btn-info'><span class='glyphicon glyphicon-edit'></span> Edit</button>" 
                    }
                ],
                initComplete: function () {
                  init = false;
                },
                createdRow: function ( row, data, index ) {
                    //assign the provider id into the row
                    $(row).attr('id', 'provider-id-'+data.id);

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
                var providerId = $(this).closest('tr').attr('id').replace('provider-id-', '');;
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
                       '<table class="child_table" id = "child_details' + index + '" cellpadding="5" cellspacing="0" border="0">'+
                       '<thead><tr><th colspan="8"><button class="add-pa-modal btn btn-info"><span class="glyphicon glyphicon-add"></span> Add</button></th></tr>'+
                       '<tr><th>Username</th><th>Password</th><th>Type</th><th>Percentage</th><th>Credits</th><th>Enabled</th><th>Idle</th><th>Options</th></tr>'+
                       '</thead><tbody></tbody></table>').show();
              
                    childTable = $('#child_details' + index).DataTable({
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
                                callback({data: display});               
                            });
                        },
                        columns: [
                            { "data": "username" },
                            { "data": "password" },
                            { "data": "type" },
                            { "data": "percentage" },
                            { "data": "credits" },
                            { "data": "is_enabled" },
                            { "data": "is_idle" },
                            { 
                                "data": null, 
                                "defaultContent": "<button class='edit-pa-modal btn btn-info'><span class='glyphicon glyphicon-edit'></span> Edit</button> <button class='delete-pa-modal btn btn-danger'><span class='glyphicon glyphicon-delete'></span> Delete</button>" 
                            }
                        ],
                        createdRow: function ( row, data, index ) {
                            //assign the provider id into the row
                            $(row).attr('id', 'provider-account-id-'+data.id);
                            //console.log(data);
                            

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

                    $('#child_details'+index+' thead').on('click', 'button.add-pa-modal', function () {
                        $('#modal-manage-provider-accounts').modal('show');
                    });

                    $('#child_details'+index+' tbody').on('click', 'button.edit-pa-modal', function () {
                        var pa_tr = $(this).closest('tr');
                        var providerAccountId = $(this).closest('tr').attr('id').replace('provider-account-id-', '');

                        var pa_is_enabled = ($(pa_tr).find('td:eq(5)').html() == 'true') ? 1 : 0;
                        var is_idle = ($(pa_tr).find('td:eq(6)').html() == 'true') ? 1 : 0;

                        var form = $('#form-manage-provider-account');
                        form.attr('data-provider-account-id', providerAccountId);
                        form.find('input[name=providerAccountId]').val(providerAccountId);
                        form.find('input[name=username]').val($(pa_tr).find('td:eq(0)').html());
                        form.find('input[name=password]').val($(pa_tr).find('td:eq(1)').html());
                        form.find('select[name=account_type]').val($(pa_tr).find('td:eq(2)').html());
                        form.find('input[name=pa_percentage]').val($(pa_tr).find('td:eq(3)').html());                        
                        form.find('select[name=provider_id]').val(providerId);
                        form.find("input[name=pa_is_enabled][value=" + pa_is_enabled + "]").prop('checked', true);
                        form.find("input[name=is_idle][value=" + is_idle + "]").prop('checked', true);


                        $('#modal-manage-provider-accounts').modal('show');

                    });

                    $('#child_details'+index+' tbody').on('click', 'button.delete-pa-modal', function () {
                        var pa_tr = $(this).closest('tr');
                        var providerAccountId = $(this).closest('tr').attr('id').replace('provider-account-id-', '');

                        swal({
                            title: "Are you sure?",
                            text: "Once deleted, you will not be able to recover this account!",
                            icon: "warning",
                            buttons: true,
                            dangerMode: true,
                        })
                        .then((willDelete) => {
                            if (willDelete) {
                                $.get('provider_accounts/delete/'+providerAccountId, function (response) {
                                    if (response.data == 'success') {
                                        swal('Provider Account', 'Provider account successfully deleted', response.data).then(() => {
                                            childTable.ajax.reload();
                                        });
                                    }
                                });
                            } else {
                                swal("Provider account is safe!");
                            }
                        });                                
                    });

                    tr.addClass('shown');
                }          
            });

            var manageProvider = function (form) {
                var btn = form.find(':submit').button('loading');
                var url = form.prop('action');
                
                $.post(url, form.serialize(), function (response) {
                    if (response.data == 'success') {
                        form.trigger('reset');
                        swal('Provider', 'Provider successfully saved', response.data).then(() => {
                            table.ajax.reload();
                        });
                    } 
                    return;
                }).done(function () {
                    btn.button('reset');
                });
            };

            $("#modal-manage-provider").on("hidden.bs.modal", function () {
                var form = $('#form-manage-provider');
                form.trigger('reset');
            });

            $.validator.setDefaults({
                submitHandler: function () {
                    var form = $('#form-manage-provider');
                    manageProvider(form);
                }
            });
            $.validator.addMethod("isUnique", function(value, element) {
                var providerLen = providerList.length;
                var unique = true;
                var isNew = $('input[name="providerId"]').val();

                for(var i = 0; i<providerLen; i++){
                    var name = providerList[i]['name'];
                    if (isNew == '' && name == value) {
                        unique = false;
                        break;
                    }
                }
                return this.optional( element ) || unique;
            }, 'Name already taken.');

            $.validator.addMethod("isUniqueAlias", function(value, element) {
                var providerLen = providerList.length;
                var unique = true;
                var isNew = $('input[name="providerId"]').val();

                for(var i = 0; i<providerLen; i++){
                    var name = providerList[i]['alias'];
                    if (isNew == '' && name == value) {
                        unique = false;
                        break;
                    }
                }
                return this.optional( element ) || unique;
            }, 'Name already taken.');

            $('#form-manage-provider').validate({
                rules: {
                    name: {
                        required: true,
                        isUnique: true
                    },
                    alias: {
                        required: true,
                        isUniqueAlias: true
                    },
                    percentage: {
                        required: true,
                        digits: true

                    },
                },
                messages: {
                    name: {
                        required: "Please enter a provider name",
                    },
                    alias: {
                        required: "Please provide an alias"
                    },
                    percentage: { 
                        required: "Percentage is required",
                        digits: "Please enter a valid percentage"
                    }

                },
                errorElement: 'span',
                    errorPlacement: function (error, element) {
                        error.addClass('invalid-feedback');
                        element.closest('div').append(error);
                    },
                    highlight: function (element, errorClass, validClass) {
                        $(element).addClass('is-invalid');
                    },
                    unhighlight: function (element, errorClass, validClass) {
                        $(element).removeClass('is-invalid');
                    }
            });

            var manageProviderAccount = function (form) {
                var btn = form.find(':submit').button('loading');
                var url = form.prop('action');
                
                $.post(url, form.serialize(), function (response) {
                    if (response.data == 'success') {
                        form.trigger('reset');
                        swal('Provider Account', 'Provider account successfully saved', response.data).then(() => {
                            $('#modal-manage-provider-accounts').modal('toggle');
                            childTable.ajax.reload();
                        });
                    } 
                    return;
                }).done(function () {
                    btn.button('reset');
                });
            };

            $("#modal-manage-provider-accounts").on("hidden.bs.modal", function () {
                var form = $('#form-manage-provider-account');
                form.trigger('reset');
            });

            $.validator.setDefaults({
                submitHandler: function () {
                    var form = $('#form-manage-provider-account');
                    manageProviderAccount(form);
                }
            });
            $('#form-manage-provider-account').validate({
                rules: {
                    username: {
                        required: true,
                    },
                    password: {
                        required: true,
                    },
                    pa_percentage: {
                        required: true,
                        digits: true

                    },
                },
                messages: {
                    username: {
                        required: "Please enter a provider account username",
                    },
                    password: {
                        required: "Please provide a password"
                    },
                    pa_percentage: { 
                        required: "Percentage is required",
                        digits: "Please enter a valid percentage"
                    }

                },
                errorElement: 'span',
                    errorPlacement: function (error, element) {
                        error.addClass('invalid-feedback');
                        element.closest('div').append(error);
                    },
                    highlight: function (element, errorClass, validClass) {
                        $(element).addClass('is-invalid');
                    },
                    unhighlight: function (element, errorClass, validClass) {
                        $(element).removeClass('is-invalid');
                    }
            });
        });
    </script>
@endsection