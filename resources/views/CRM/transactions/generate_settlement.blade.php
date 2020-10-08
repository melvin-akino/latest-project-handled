<div class="modal fade" id="modal-generate-settlement" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" name="form-generate-settlement" id="form-generate-settlement"
                  action="{{ route('transactions.generate_settlement') }}"
                  method="POST">
                  {{ csrf_field() }}
                  <input type="hidden" class="form-control" name="orderId"
                                   id="orderId"
                                   placeholder="errorId">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Generate Settlement</h4>
                </div>
                <div class="modal-body">
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

                    assocErr(xhr.responseJSON.errors, form);

                    btn.button('reset');
                });
            };

            $("#modal-generate-settlement").on("hidden.bs.modal", function () {
                var form = $('#form-generate-settlement');
                form.find('input[name=orderId]').val();                
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
