@extends('CRM.layouts.dashboard')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li><a href="/admin"><i class="fa fa-dashboard"></i> Home</a></li>
        <li>Wallet</li>
        <li class="active">Currency</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-body">
                    <table id="currrency-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Symbol</th>
                            <th>Currency Code</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                    </table>
                </div>
               
                <div class="box-footer">
                    <button type="button" class="btn btn-success btn-xs btn-add-currency"
                            data-toggle="modal"
                            data-target="#modal-add-currency" title="Add">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
               
            </div>
        </div>
    </div>

    @include('CRM.wallet.currency.forms.add')
    @include('CRM.wallet.currency.forms.edit')
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet"
          href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css") }}">
@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net/js/jquery.dataTables.min.js") }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js") }}"></script>
@endsection

@section('script')
    @parent

    <!-- ClubNine -->
    <script src="{{ asset("CRM/Capital7-1.0.0/js/form-validation.js") }}"></script>

    <script>
        var setFormEditCurrency = function (src) {
            var form = $('#form-edit-currency');
            var tr = $(src).closest('tr');
            form.attr('data-currency-id', tr.data('currency-id'));
            form.find('input[name=currency_name]').val(tr.find('td:eq(0)').text());
            form.find('input[name=currency_symbol]').val(tr.find('td:eq(1)').text());
            form.find('input[name=currency_code]').val(tr.find('td:eq(2)').text());
           
            $('#modal-edit-currency').modal('show');
        };

        $(function () {
            $('#currrency-table').DataTable({
                "processing": true,
                "serverSide": true,
                "stateSave": true,
                "responsive": true,
                "ajax": {
                    "url": "/admin/wallet/currencies/datatable",
                    "error": function($err){
                        if($err.status == 401){
                            alert('Your session is expired! Please login again.');
                            window.location.href = '/login';
                        }
                    }
                },
                'createdRow': function (tr, data, dataIndex) {
                    $(tr).attr('data-currency-id', data.id);
                },
               
                "columns": [
                    {"data": "name"},
                    {"data": "symbol"},
                    {"data": "code"},{
                        "render": function (data, type, row, meta) {
                            data = '';

                                    data += '<button type="button" onclick="setFormEditCurrency(this);" class="btn btn-warning btn-xs" title="Edit"><i class="fa fa-edit"></i></button>';
                               
                            return data;
                        }
                  }                 
                ]
            });

        });
    </script>
@endsection