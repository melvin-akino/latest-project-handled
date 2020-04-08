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
                        <td>Debit</td>
                        <td>Credit</td>
                        <td>Balance</td>
                        <td>Method</td>
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

            $.getJSON('ledger/' + btn.data('wallet-ledger-id') + '/source-info', function (data) {
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
                    "bFilter": false,
                    "responsive": true,
                    "ajax": 'wallet/datatable/'+ walletId,
                    columnDefs: [{
                        targets: 5,
                        orderable: false
                    }],
                    "order": [
                        [0, "desc"]
                    ],
                    "columns": [
                        {
                            "data": "id",

                        },
                        {"data": "created_at"}, 
                        {
                            "data": "debit",
                            "render": function (data, type, row, meta) {
                                if (type === 'display') {
                                    data = row.userwallet.currency.code + ' ' + parseFloat(row.debit).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,");

                                }
                                return data;
                            }
                        },
                        {
                            "data": "credit",
                            "render": function (data, type, row, meta) {
                                if (type === 'display') {
                                    data = row.userwallet.currency.code + ' ' + parseFloat(row.credit).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,");
                                }
                                return data;
                            }
                        },
                        {
                            "data": "balance",
                            "render": function (data, type, row, meta) {
                                if (type === 'display') {
                                    data = row.userwallet.currency.code + ' ' + parseFloat(row.balance).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,");
                                }
                                return data;
                            }
                        },
                        {
                            "data": 'source_id',
                            "render": function (data, type, row, meta) {
                                if (type === 'display') {
                                    var sourceName = row.source.source_name.replace(/([A-Z]+)/g, " $1").replace(/([A-Z][a-z])/g, " $1");


                                    data = '<button type="button" onclick="getSourceInfo(this);" data-loading-text="{{ trans('loading.default') }}" class="btn btn-primary btn-xs source-link" data-wallet-ledger-id="' + row.id + '" data-source-name="' + sourceName + '">' + sourceName + '</button>';
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