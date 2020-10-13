<div class="modal fade" id="modal-generate-settlement" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" name="form-generate-settlement" id="form-generate-settlement"
                    action="{{ route('unsettled_transactions.generate_settlement') }}"
                    method="POST">
                    {{ csrf_field() }}
                    <input type="hidden" class="form-control" name="bet_id" id="bet_id" placeholder="bet_id">
                    <input type="hidden" class="form-control" name="sport" id="sport" placeholder="sport">
                    <input type="hidden" class="form-control" name="provider" id="provider" placeholder="provider">
                    <input type="hidden" class="form-control" name="username" id="username" placeholder="username">
                    <input type="hidden" class="form-control" name="odds" id="odds" placeholder="odds">
                    <input type="hidden" class="form-control" name="stake" id="stake" placeholder="stake">
                    <input type="hidden" class="form-control" name="towin" id="towin" placeholder="towin">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Generate Settlement</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="add-currency-name-input" class="col-sm-3 control-label">Bet Selection</label>
                        <div class="col-sm-8" id="bet_selection">
                            
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="add-currency-name-input" class="col-sm-3 control-label">Stake</label>
                        <div class="col-sm-8" id="actual_stake">
                            
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="add-currency-name-input" class="col-sm-3 control-label">To Win</label>
                        <div class="col-sm-8" id="to_win">
                            
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="add-currency-name-input" class="col-sm-3 control-label">Status</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="status"
                                   id="status"
                                   placeholder="Status">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="add-currency-name-input" class="col-sm-3 control-label">Score</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="score"
                                   id="score"
                                   placeholder="Score">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="add-currency-name-input" class="col-sm-3 control-label">PL</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="pl"
                                   id="pl"
                                   placeholder="Profit and Loss">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="add-currency-name-input" class="col-sm-3 control-label">Reason</label>
                        <div class="col-sm-8">
                            <input type="textarea" class="form-control" name="reason"
                                   id="reason"
                                   placeholder="Reason">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                    <button type="submit" role="button" data-loading-text='{{ trans('loading.please_wait') }}'
                            class="btn btn-primary">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@section('script')
    @parent
    <script>
        $(function () {
            var generateSettlement = function (form) {
                var btn = form.find(':submit').button('loading');
                var url = form.prop('action');

                $.post(url, form.serialize(), function (response) {
                    
                    if (response.data == 'success') {
                        swal('Settlement', 'Settlement request has been successfully generated.', response.data).then(() => {
                            $('#modal-generate-settlement').modal('toggle');
                            form.trigger('reset');
                            table.ajax.reload();
                        });
                    }
                    return;
                }).done(function () {
                    btn.button('reset');
                }).fail(function(xhr, status, error) {
                    swal('Settlement', 'This bet id has already been sent out for settlement.', 'warning');
                    btn.button('reset');
                });
            };

            $("#modal-generate-settlement").on("hidden.bs.modal", function () {
                var form = $('#form-generate-settlement');
                form.find('input[name=bet_id]').val();                
                form.trigger('reset');
                clearErr(form);
            });

            $('#form-generate-settlement').submit(function(e) {
                e.preventDefault();
                generateSettlement($(this));
            });
        });
    </script>
@endsection
