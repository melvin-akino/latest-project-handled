@extends('CRM.layouts.dashboard')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li><a href="/admin"><i class="fa fa-dashboard"></i> Home</a></li>
        <li>Wallet</li>
        <li class="active">{{ $page_title }}</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-body">
                    <table id="transfer-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th> Wallet </th>
                            <th>Actions</th>
                           
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('CRM.wallet.transfer.forms.transfer')
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet"
          href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css") }}">

    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset("CRM/AdminLTE-2.4.2/bower_components/select2/dist/css/select2.min.css") }}">
@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net/js/jquery.dataTables.min.js") }}"></script>
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js") }}"></script>

    <!-- Select2 -->
    <script src="{{ asset("CRM/AdminLTE-2.4.2/bower_components/select2/dist/js/select2.full.min.js") }}"></script>
@endsection

@section('script')
    @parent

    <script>
        $(function () {
            $('#transfer-table').DataTable({
                "processing": true,
                "serverSide": true,
                "stateSave": true,
                "responsive": true,
                "ajax": "/admin/wallet/transfer/datatable",
                columnDefs: [{
                       
                        targets: 3,
                        orderable: false
                   
                }],
                "columns": [
                    {
                        "data": 'firstname',
                    },
                    {
                        "data": 'lastname',
                    },
                    {
                        "data": 'email',
                    },
                    {
                       "data": 'code' ,

                        "render": function (data, type, row, meta) {                            
                            balance = row.balance;;
                            if (balance==null) balance ='0.00'; 

                            balance = parseFloat(balance).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');;
                            return  row.code + ' '+ balance;            
                        }
                    },
                        
                    {
                        
                        "render": function (data, type, row, meta) {
                            data = '';
                                                            
                                    data += '<a href="#" data-currency-id="' + row.currency_id + '" data-user-id="' + row.userid + '" class="transfer-wallet-link"><i class="fa fa-share-square-o"></i>{{ $page_title }} Funds</a>';                           
                            return data;
                        }
                    }
                    
                ]
            });

            $('#transfer-table').on('click', '.transfer-wallet-link', function () {
                var tr = $(this).closest('tr');
                    var form = $('#form-transfer');
                form.find('#email').text(tr.find('td:eq(2)').text());
                form.find('input[name=user_id]').val($(this).data('user-id'));

                $('#currency-id-select').val($(this).data('currency-id')).trigger('change');

                $('#modal-transfer').modal('show');
            });
        });
    </script>

@endsection