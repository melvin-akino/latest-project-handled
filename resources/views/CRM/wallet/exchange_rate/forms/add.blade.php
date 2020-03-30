<div class="modal fade" id="modal-add-exchange-rate" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form name="form-add-exchange-rate" id="form-add-exchange-rate"
                  action="{{ route('exchange_rates.store') }}"
                  method="POST">
                {{ csrf_field() }}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Add Exchange Rate </h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <input type="number" class="form-control"  id="add-default-amount-input" name="default_amount" value="{{   number_format($default_amount,2) }}" placeholder="Default amount" readonly>
                            </div>
                              
                            <div class="form-group">
                                <select class="form-control" id="add-from-currency-select" name="from_currency" data-width="100%">
                                    <option value="" disabled selected>Select from currency</option>
                                    @foreach($in_app_currencies as $currency)
                                        <option value="{{ $currency->id }}" >{{ $currency->name }}</option>
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
                                <input type="number" class="form-control"   id="add-exchange-rate-input" name="exchange_rate" placeholder="Enter exchange rate" data-placeholder="Enter exchange rate" min="0.00"    step="any">
                            </div>
                            <div class="form-group">
                                <select class="form-control" id="add-to-currency-select" name="to_currency" data-width="100%">
                                    <option value="" disabled selected>Select to currency</option>
                                    @foreach($in_app_currencies as $currency)
                                        <option value="{{ $currency->id }}">{{ $currency->name }} </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                   
                    <button type="submit" role="button" id="submit-btn" data-loading-text='{{ trans('loading.please_wait') }}'
                            class="btn btn-primary">
                        Save
                    </button>
                   
                </div>
            </form>
        </div>
    </div>
</div>

@section('style')
    @parent

    <style>
        #convert-currency {
            padding-top: 20px;
        }
    </style>
@endsection

@section('script')
    @parent

    <script>
        $(function () {
            var form = $('#form-add-exchange-rate');

            form.find('#add-to-currency-select').on('change', function () {
                form.find('#add-from-currency-select option').show();

                if ($(this).val() != null) {
                    form.find('#add-exchange-rate-input').prop('placeholder', 'Enter value in ' + $(this).find('option:selected').text());
                    form.find('#add-from-currency-select option[value=' + $(this).val() + ']').hide();
                    return;
                }
                form.find('#exchange-rate-input').prop('placeholder', 'Enter exchange rate');
            });

            form.find('#add-from-currency-select').on('change', function () {
                form.find('#add-to-currency-select option').show();

                if ($(this).val() != null) {
                    form.find('#add-to-currency-select option[value=' + $(this).val() + ']').hide();
                    return;
                }
            });

            $('#add-to-currency-select option[value=' + $('#add-from-currency-select').val() + ']').hide();

            $("#modal-add-exchange-rate").on("hidden.bs.modal", function () {
                form.find('#add-from-currency-select').val('').trigger('change');
                form.find('#add-to-currency-select').val('').trigger('change');
                form.find('#add-exchange-rate-input').prop('placeholder', form.find('#add-exchange-rate-input').data('placeholder'));
                form.trigger('reset');
                clearErr(form);
            });

            form.submit(function (e) {
                e.preventDefault();

                var btn = form.find(':submit').button('loading');
                var url = form.prop('action');

                $.post(url, form.serialize(), function (response) {
                    if (response.status == '{{ config('response.type.success') }}') {
                        swal('Exchange Rate', 'Saved Succesfully!', response.status).then(() => {
                            location.href = url;
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