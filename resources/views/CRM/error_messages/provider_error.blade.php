@extends('CRM.layouts.dashboard')

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet"
          href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css") }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb">
        <li><a href="/admin"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Error</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">All Provider Error</h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <table id="accounts-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>Provider Message</th>
                            <th>Error Message</th>
                           
                            <th>Actions</th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <!-- /.box-body -->

                <div class="box-footer">
                    <a href="#" class="btn btn-default" data-toggle="modal" data-target="#modal-add-account">Add New Error</a>
                </div>
            </div>
        </div>
    </div>

    @include('CRM.error_messages.provider_error_add')
@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net/js/jquery.dataTables.min.js") }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js") }}"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.10.19/dataRender/ellipsis.js"></script>
@endsection

@section('script')
    @parent

     <script src="{{ asset('CRM/Capital7-1.0.0/js/form-validation.js') }}"></script>

    <script>
        $(function(){
            $("#modal-add-account").on("hidden.bs.modal", function () {
                var form = $('#formaddaccount');
                form.trigger('reset');
                clearErr(form);
            });

            $('#accounts-table').DataTable({
                "processing": true,
                "serverSide": true,
                "stateSave": true,
                "responsive": true,
                "ajax": {
                    "url": "/admin/message/datatable",
                    "error": function($err){
                        if($err.status == 401){
                            alert('Your session is expired! Please login again.');
                            window.location.href = '/admin/login';
                        }
                    }

                },
                columnDefs: [{
                       
                        targets: 2,
                        orderable: false
                   
                }],
                "columns": [
                    {
                        "data": "message",
                        "render": function (data, type, row, meta) {
                            if (type === 'display') {
                                data = !row.message ? 'N/A' : row.message;
                            }
                            return data;
                        }
                    },

                    {
                        "data": "errorvalue",
                        "render": function (data, type, row, meta) {
                            if (type === 'display') {
                                data = !row.errorvalue.error ? 'N/A' : row.errorvalue.error;
                            }
                            return data;
                        }
                    },

                    
                    {
                        "data": null,
                        "render": function (data, type, row, meta) {
                            if (type === 'display') {
                                data = '<a href="/admin/accounts/' + row.id + '#personal"><i class="fa fa-user-o"></i> Details</a>';
                            }
                            return data;
                        }
                    }
                ]
            });

            
        });
    </script>
@endsection
