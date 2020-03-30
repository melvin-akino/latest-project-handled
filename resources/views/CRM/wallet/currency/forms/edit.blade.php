<div class="modal fade" id="modal-edit-currency" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" name="form-edit-currency" id="form-edit-currency"
                  data-temp-action="/admin/wallet/currencies"
                  method="POST">
                {{ csrf_field() }}
                {{ method_field('PUT') }}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Edit Currency</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit-currency-name-input" class="col-sm-3 control-label">Name</label>

                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="currency_name"
                                   id="edit-currency-name-input"
                                   placeholder="Name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit-currency-symbol-input" class="col-sm-3 control-label">Symbol</label>

                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="currency_symbol"
                                   id="edit-currency-symbol-input"
                                   placeholder="Symbol">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit-currency-code-input" class="col-sm-3 control-label">Currency Code</label>

                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="currency_code" id="edit-currency-code-input"
                                   placeholder="Currency Code">
                        </div>
                    </div>

                  

                  
                    <input type="hidden" name="confirm">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                   
                    <button type="submit" role="button" data-loading-text='{{ trans('loading.please_wait') }}' class="btn btn-primary">
                        Save changes
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
            $("#modal-edit-currency").on("hidden.bs.modal", function () {
                var form = $('#form-edit-currency');
                form.removeAttr('data-currency-id');
                form.trigger('reset');
                clearErr(form);
            });

            $('#form-edit-currency').submit(function (e) {
                e.preventDefault();
                var form = $(this);
                var btn = form.find(':submit').button('loading');
                var url = form.data('temp-action') + '/' + form.data('currency-id');

                $.post(url, form.serialize(), function (response) {
                    if (response.status == '{{ config('response.type.success') }}') {
                        swal('Currency', 'Saved Succesfully!', response.status).then(() => {
                            location.href = form.data('temp-action');
                        });

                        return;
                    } else if (response.status == '{{ config('response.type.error') }}') {
                        if (Object.keys(response.errors).length == 1 && response.errors.confirm) {
                            swal({
                                title: '{{ trans('swal.currency.update.confirm.title') }}',
                                html: '{{ trans('swal.currency.update.confirm.html') }}',
                                type: '{{ trans('swal.currency.update.confirm.type') }}',
                                showCancelButton: true,
                                confirmButtonText: '{{ trans('swal.currency.update.confirm.confirmButtonText') }}'
                            }).then(function (result) {
                                if (result.value) {
                                    form.find('input[name=confirm]').val(true);
                                    form.submit();
                                }
                            });
                            delete response.errors.confirm;
                        }
                        assocErr(response.errors, form);
                    }
                }).done(function () {
                    btn.button('reset');
                }).fail(function(xhr, status, error) {
                    // error handling
                    assocErr(xhr.responseJSON.errors, form);

                    btn.button('reset');
                });
            });
        });
    </script>

@endsection
