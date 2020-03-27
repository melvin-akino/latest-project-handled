<div class="modal fade" id="modal-add-currency" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" name="form-add-currency" id="form-add-currency"
                  action="{{ route('currencies.store') }}"
                  method="POST">
                {{ csrf_field() }}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Add Currency</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="add-currency-name-input" class="col-sm-3 control-label">Name</label>

                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="currency_name"
                                   id="add-currency-name-input"
                                   placeholder="Name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="add-currency-symbol-input" class="col-sm-3 control-label">Symbol</label>

                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="currency_symbol"
                                   id="add-currency-symbol-input"
                                   placeholder="Symbol">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="add-currency-code-input" class="col-sm-3 control-label">Currency Code</label>

                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="currency_code" id="add-currency-code-input"
                                   placeholder="Currency Code">
                        </div>
                    </div>

                  

                    <input type="hidden" name="confirm">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                  
                    <button type="submit" role="button" data-loading-text='{{ trans('loading.please_wait') }}' class="btn btn-primary">
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
            $("#modal-add-currency").on("hidden.bs.modal", function () {
                var form = $('#form-add-currency');
                form.trigger('reset');
                clearErr(form);
            });

            $('#form-add-currency').submit(function (e) {
                e.preventDefault();
                var form = $(this);
                var btn = form.find(':submit').button('loading');
                var url = form.prop('action');

                $.post(url, form.serialize(), function (response) {
                    if (response.status == '{{ config('response.type.success') }}') {
                        swal('Currency', 'Saved Succesfully!', response.status).then(() => {
                            location.href = url;
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