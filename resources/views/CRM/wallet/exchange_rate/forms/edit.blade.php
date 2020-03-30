<div class="modal fade" id="modal-edit-exchange-rate" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form name="form-edit-exchange-rate" id="form-edit-exchange-rate"
                  data-temp-action="/admin/wallet/exchange_rates"
                  method="POST">
                {{ csrf_field() }}
                {{ method_field('PUT') }}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Edit Exchange Rate  </h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <input type="number" class="form-control"  id="edit-default-amount-input"  value="{{  number_format($default_amount,2) }}" placeholder="Default amount" readonly  >
                       
                            </div>

                            <div class="form-group">
                                <select class="form-control" id="edit-from-currency-select" name="from_currency" data-width="100%" disabled>
                                    <option value="" disabled selected>Select from currency</option>
                                    @foreach($in_app_currencies as $currency)
                                        <option value="{{ $currency->id }}">{{ $currency->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <a href="#" id="convert-currency" class="col-md-4 col-md-offset-1">
                                <i class="fa fa-long-arrow-right"></i>
                                <i class="fa fa-long-arrow-right"></i>
                            </a>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <input type="number" class="form-control" min="0.00"  step="any" id="edit-exchange-rate-input" name="exchange_rate" placeholder="Enter exchange rate" data-placeholder="Enter exchange rate" >
                            </div>

                            <div class="form-group">
                                <select class="form-control" id="edit-to-currency-select" name="to_currency" data-width="100%" disabled>
                                    <option value="" disabled selected>Select to currency</option>
                                    @foreach($in_app_currencies as $currency)
                                        <option value="{{ $currency->id }}">{{ $currency->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
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

@section('scripts')
    @parent

    <script>
        
        $(function () {

            var form = $('#form-edit-exchange-rate');

            form.find('#edit-to-currency-select').on('change', function () {
                form.find('#edit-from-currency-select option').show();

                if ($(this).val() != null) {
                    form.find('#edit-exchange-rate-input').prop('placeholder', 'Enter value in ' + $(this).find('option:selected').text());
                    form.find('#edit-from-currency-select option[value=' + $(this).val() + ']').hide();
                    return;
                }
                form.find('#exchange-rate-input').prop('placeholder', 'Enter exchange rate');
            });

            form.find('#edit-from-currency-select').on('change', function () {
                form.find('#edit-to-currency-select option').show();

                if ($(this).val() != null) {
                    form.find('#edit-to-currency-select option[value=' + $(this).val() + ']').hide();
                    return;
                }
            });

            $("#modal-edit-exchange-rate").on("hidden.bs.modal", function () {
                form.removeAttr('data-exchange-rate-id');
                form.find('#edit-from-currency-select').val('').trigger('change.select2');
                form.find('#edit-to-currency-select').val('').trigger('change.select2');
                form.find('#edit-exchange-rate-input').prop('placeholder', form.find('#edit-exchange-rate-input').data('placeholder'));
                form.trigger('reset');
                clearErr(form);
            });

            form.submit(function (e) {
                e.preventDefault();
                var btn = form.find(':submit').button('loading');
                var url = form.data('temp-action') + '/' + form.data('exchange-rate-id');

                form.find('#edit-from-currency-select').prop('disabled', false);
                form.find('#edit-to-currency-select').prop('disabled', false);

                $.post(url, form.serialize(), function (response) {
                    if (response.status == '{{ config('response.type.success') }}') {
                        swal('Exchange Rate', 'Saved Succesfully!', response.status).then(() => {
                            location.href = form.data('temp-action');
                        });

                        return;
                    } else if (response.status == '{{ config('response.type.error') }}') {
                        swal('Exchange Rate', 'Something went wrong.', 'error').then(() => {
                            assocErr(response.errors, form);
                        });
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
