<div class="modal fade" id="modal-transactions" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title text-center">
                    <strong id="wallet-account">Wallet Transactions</strong>
                </h4>
            </div>
            <div class="modal-body">
                <table id="transactions-table" class="table table-striped table-bordered" cellspacing="0"
                       width="100%">
                    <thead>
                    <tr>
                        <td>Transaction ID</td>
                        <td>Created At</td>
                        <td>Credit</td>
                        <td>Debit</td>
                        <td>Balance</td>
                        <td>Status</td>
                    </tr>
                    </thead>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-right" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-transaction-source" data-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title text-center">
                    <strong>
                        <span id="source"></span>
                    </strong>
                </h4>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-right" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@section('style')
    @parent

    <style>
        .wallet-source small {
            font-size: 11px;
        }

        .wallet-source img {
            height: 120px;
            padding-top: 10px;
            padding-bottom: 10px;
        }

        code {
            color: #000000;
            background-color: #ffffff;
        }

        .wallet-currency-entry .panel-body {
            padding-top: 0px;
        }

        .wallet-currency-entry .panel-body .row {
            margin-top: -10px;
        }
    </style>
@endsection

@section('script')
    @parent

    <script>
        var getSourceInfo = function (src) {
            var btn = $(src);
            btn.button('loading');
            var modal = $('#modal-transaction-source');
            modal.find('#source').text(btn.data('source-name'));

            $.getJSON('/admin/wallet/ledger/' + btn.data('wallet-ledger-id') + '/source-info', function (data) {
                modal.find('.modal-body').html(data.html);
            }).done(function () {
                btn.button('reset');
                modal.modal('show');
            });
        };

        var parseJSONWallet = function(json){

        }


        function pad(num, size) {
            var s = num+"";
            while (s.length < size) s = "0" + s;
            return s;
        }

        $(function () {
            $('.more-details-link').on('click', function () {
                var btn = $(this);
                btn.button('loading');
                var walletId = btn.data('wallet-id');
                var modal = $('#modal-transactions');

                var table = $('#transactions-table').DataTable({
                    "processing": true,
                    "serverSide": true,
                    "responsive": true,
                    "ajax": '/admin/wallet/' + walletId + '/datatable',
                    columnDefs: [{
                        targets: 5,
                        orderable: false
                    }],
                    "order": [
                        [0, "desc"]
                    ],
                    "columns": [
                        {
                            "data": "wallet_ledger_id",
//                            "render" : function (data, type, row, meta) {
//                                //console.log(row.json);
//                                //return row.json;
//                                json_data = JSON.parse(row.json_data_output);
//
//                                if(row.transaction_number) {
//                                    return row.transaction_number;
//                                }
//
//                                var type_initial = '';
//
//                                var trans_type =  Object.keys(json_data)[0];
//                                switch(trans_type) {
//                                    case 'Deposited':
//                                        type_initial = 'DP';
//                                        break;
//                                    case 'Withdrawn':
//                                        type_initial = 'WP';
//                                        break;
//                                    case 'Redeemed':
//                                        type_initial = 'RP';
//                                        break;
//                                    default:
//                                        type_initial = 'ERR'
//                                }
//
//                                var dy1   = parseInt(json_data.time.substring(0,2));
//                                var mon1  = parseInt(json_data.time.substring(3,5));
//                                var yr1   = parseInt(json_data.time.substring(6,10));
//
//                                return 'SP' + type_initial + yr1 + mon1 + dy1 + pad(row.wallet_ledger_id, 4)
//
//
//                            }
                        },
                        {"data": "created_at"},
                        {
                            "data": "debit",
                            "render": function (data, type, row, meta) {
                                if (type === 'display') {
                                    data = row.wallet.currency.currency_symbol + ' ' + parseFloat(row.debit).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,");
                                }
                                return data;
                            }
                        },
                        {
                            "data": "credit",
                            "render": function (data, type, row, meta) {
                                if (type === 'display') {
                                    data = row.wallet.currency.currency_symbol + ' ' + parseFloat(row.credit).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,");
                                }
                                return data;
                            }
                        },
                        {
                            "data": "balance",
                            "render": function (data, type, row, meta) {
                                if (type === 'display') {
                                    data = row.wallet.currency.currency_symbol + ' ' + parseFloat(row.balance).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,");
                                }
                                return data;
                            }
                        },
                        {
                            "data": 'source_id',
                            "render": function (data, type, row, meta) {
                                if (type === 'display') {
                                    var sourceName = row.source.source_name.replace(/([A-Z]+)/g, " $1").replace(/([A-Z][a-z])/g, " $1");


                                    data = '<button type="button" onclick="getSourceInfo(this);" data-loading-text="{{ trans('loading.default') }}" class="btn btn-primary btn-xs source-link" data-wallet-ledger-id="' + row.wallet_ledger_id + '" data-source-name="' + sourceName + '">' + sourceName + '</button>';
                                }
                                return data;
                            }
                        }
                    ],
                    "fnInitComplete": function(oSettings, json) {
                        btn.button('reset');
                        console.log(json);
//                        modal.find('#wallet-account').text(json.data[0].wallet.currency.currency_name);
                        modal.modal('show');
                    }
                });

                modal.on("hidden.bs.modal", function () {
                    table.destroy();
                });
            });
        });
    </script>
@endsection