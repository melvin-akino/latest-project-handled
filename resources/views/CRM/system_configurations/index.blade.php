@extends('CRM.layouts.dashboard')
@section('breadcrumb')
    <ol class="breadcrumb">
        <li><i class="fa fa-dashboard"></i> Home</li>
        <li class="active">System Configurations</li>
    </ol>
@endsection
@section('content')
    <div class="row">
        <form class="form-horizontal" name="form-manage-config" id="form-manage-config" action="{{ route('system_configurations.manage') }}" method="POST">
        {{ csrf_field() }} 
        <table class="table table-striped" id="SystemConfigTable">
        <thead>
            <tr>
                <th class="text-left">Type</th>
                <th class="text-left">Value</th>
                <th class="text-left">Module</th>
                <th class="text-left">Action</th>
            </tr>
        </thead>
        </table>
        </form>
    </div>
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css") }}">
@endsection

@section('scripts')

    <!-- DataTables -->  
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net/js/jquery.dataTables.min.js") }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js") }}"></script>
    <script src="{{ asset("CRM/Capital7-1.0.0/js/form-validation.js") }}"></script>

    <script type="text/javascript" >
        
        var table;

        $(document).ready(function() {
            table = $('#SystemConfigTable').DataTable( {
                paging:   false,
                info:     false,
                searching: false,
                ordering: false,
                autoWidth: true,
                ajax: function (data, callback, settings) {
                    $.ajax({
                        url: "system_configurations/all",
                    }).then ( function(json) {
                        callback(json);            
                    });
                },
                pageLength: 10,
                columns: [
                    { "data": "type" },
                    { "data": "value" },
                    { "data": "module" },
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
                    $(row).attr('id', 'config-id-'+data.id);
                },
                rowCallback: function ( row, data, index ) {

                }
            });

            $('#SystemConfigTable tbody').on('click', 'button.edit', function (e) {
                e.preventDefault(e);

                var $row = $(this).closest("tr").off("mousedown");


                var configId = $(this).closest("tr").attr('id').replace('config-id-','');
                revertUpdateInterface(configId);

                var type = $row.find("td:first").text();              
                //Find the editable fields
                $row.find("td:first").append("<input type='hidden' name='id' value='"+configId+"'/>");
                $row.find("td:first").append("<input type='hidden' name='type' value='"+type+"'/>");
                var $tds = $row.find("td").not(':first').not(':last');

                var nameId = '';
                $.each($tds, function(i, el) {
                    switch(i) {
                        case 0 : 
                            nameId = 'value';
                            break;
                        case 1: 
                            nameId = 'module';
                            break;
                    }
                    var txt = $(this).text();
                    $(this).html("").append("<div class='form-group'><input type='text' name='"+nameId+"' value='"+txt+"'/></div>");
                });                          
                
                $(this).removeClass().addClass("save btn btn-success").text('Save');
                //Create a new button called Cancel
                $(this).closest("td").append(" <button class='cancel btn btn-danger'>Cancel</button>");

                

            });

            $("#SystemConfigTable").on('mousedown', "input", function(e) {
                e.stopPropagation();
            });

            $('#SystemConfigTable tbody').on('click', 'button.save', function (e) {
                //Submit the form via ajax post
                e.preventDefault(e);
                var form = $("#form-manage-config");
                manageConfig(form);
            });

            $('#SystemConfigTable tbody').on('click', 'button.cancel', function (e) {
                e.preventDefault(e);

                table.ajax.reload();
            });

            var revertUpdateInterface = function(configId) {
                //get all rows and skip the current configId in reverting the interface
                $("#SystemConfigTable > tbody > tr").each(function () {
                    var dataId = $(this).closest("tr").attr('id').replace('config-id-','');
                    if (dataId != configId) {
                        var $row = $(this).closest("tr");
                        var $tds = $row.find("td").not(':first').not(':last');

                        $row.find("td:first > input[type='hidden']").remove();
                        $row.find("td:last > button.cancel").removeClass().addClass("edit btn btn-info").text('Edit');
                        $row.find("td:last > button.save").remove();

                        $.each($tds, function(i, el) {
                            var txt = $(this).find("input").val();
                            $(this).html(txt);
                        });                       
                    }                       
                });
            }

            var manageConfig = function (form) {
                var url = form.prop('action');            
                $.post(url, form.serialize(), function (response) {
                  
                    if (response.data == 'success') {
                        swal('System Configurations', 'Configurations successfully updated.', response.data).then(() => {
                            table.ajax.reload();
                        });
                    }
                    return;
                }).done(function () {
                    clearErr(form);
                }).fail(function(xhr, status, error) {
                    assocErr(xhr.responseJSON.errors, form);
                });
            };
        });
    </script>
@endsection