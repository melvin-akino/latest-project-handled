@extends('CRM.layouts.dashboard')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li><a href="/admin"><i class="fa fa-dashboard"></i> Home</a></li>
        <li>Wallet</li>
        <li class="active">Exchange Rate</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-body">
                    <table id="exchange-rate-table" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Default Amount</th>
                            <th>Exchange Rate</th>
                           
                            <th>Actions</th>
                           
                        </tr>
                        </thead>
                    </table>
                </div>
              
                <div class="box-footer">
                    <button type="button" class="btn btn-success btn-xs btn-add-exchange-rate"
                            data-toggle="modal"
                            data-target="#modal-add-exchange-rate" title="Add">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
                
            </div>
        </div>
    </div>

    @include('CRM.wallet.exchange_rate.forms.add')
    @include('CRM.wallet.exchange_rate.forms.edit')
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

    <!-- ClubNine -->
    <script src="{{ asset("CRM/Capital7-1.0.0/js/form-validation.js") }}"></script>

    <script>
        var setFormEditExchangeRate = function (src) {
            var form = $('#form-edit-exchange-rate');
            var tr = $(src).closest('tr');
            form.attr('data-exchange-rate-id', tr.data('exchange-rate-id'));
            form.find('select[name=from_currency]').val(tr.find('td:eq(0)').data('currency-id')).trigger('change');
            form.find('select[name=to_currency]').val(tr.find('td:eq(1)').data('currency-id')).trigger('change');
            form.find('input[name=default_amount]').val(tr.find('td:eq(2)').data('default-amount'));
            form.find('input[name=exchange_rate]').val(tr.find('td:eq(3)').data('exchange-rate'));
            $('#modal-edit-exchange-rate').modal('show');
        };

        $(function () {
            $('#exchange-rate-table').DataTable({
                "processing": true,
                "serverSide": true,
                "stateSave": true,
                "searching": false,
                "responsive": true,
                "ajax": "{{ route('wallet.exchange_rates.dataTable') }}",
                'createdRow': function (tr, data, dataIndex) {
                    $(tr).attr('data-exchange-rate-id', data.id);
                },
                columnDefs: [
                    {
                        'targets': 0,
                        'createdCell': function (td, cellData, rowData, row, col) {
                            $(td).attr('data-currency-id', cellData);
                        }
                    }, {
                        'targets': 1,
                        'createdCell': function (td, cellData, rowData, row, col) {
                            $(td).attr('data-currency-id', cellData);
                        }
                    }, {
                        'targets': 2,
                        'createdCell':  function (td, cellData, rowData, row, col) {
                            $(td).attr('data-default-amount', cellData);
                        }
                    }, {
                        'targets': 3,
                        'createdCell':  function (td, cellData, rowData, row, col) {
                            $(td).attr('data-exchange-rate', parseFloat(cellData).toFixed(12));
                        }
                    },
                        
                    {
                        targets: 4,
                        orderable: false
                    }
                    
                ],
                "columns": [
                    {
                        "data": "from_currency_id",
                        "render": function (data, type, row, meta) {
                            if (type === 'display') {
                                data = row.currency_from.code;
                            }
                            return data;
                        }
                    },
                    {
                        "data": "to_currency_id",
                        "render": function (data, type, row, meta) {
                            if (type === 'display') {
                                data = row.currency_to.code;
                            }
                            return data;
                        }
                    },
                    {
                        "data": "default_amount",
                        "render": function (data, type, row, meta) {
                            if (type === 'display') {
                                data = parseFloat(row.default_amount).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,");
                            }
                            return data;
                        }
                    },
                    {
                        "data": "exchange_rate",
                        "render": function (data, type, row, meta) {
                            if (type === 'display') {
                                data = parseFloat(row.exchange_rate).toFixed(12).replace(/(\d)(?=(\d{3})+\.)/g, "$1,");
                            }
                            return data;
                        }
                    },
                   {
                        
                        "render": function (data, type, row, meta) {
                            data = '';

                           
                                
                                    data += '<button type="button" onclick="setFormEditExchangeRate(this);" class="btn btn-warning btn-xs" title="Edit"><i class="fa fa-edit"></i></button>';
                               
                           
                            return data;
                        }
                    }

                  
                ]
            });
        });
    </script>

@endsection