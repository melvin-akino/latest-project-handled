<div id="billing-address" class="tab-pane fade">
    <div class="row">
        <div class="col-md-4">
            <form name="form-add-billing-address" id="form-add-billing-address"
                  action="{{ route('accounts.billing_addresses.store', $account) }}#billing-address"
                  method="POST">
                {{ csrf_field() }}
                <div class="form-group{{ $errors->has('ba_first_name') ? ' has-error' : '' }}">
                    <label for="first-name-input" class="control-label">First Name</label>
                    <input type="text" class="form-control" id="first-name-input" name="ba_first_name"
                           placeholder="First Name" value="{{ old('ba_first_name') }}">

                    @if ($errors->has('ba_first_name'))
                        <span class="help-block">
                            <strong>{{ $errors->first('ba_first_name') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="form-group{{ $errors->has('ba_last_name') ? ' has-error' : '' }}">
                    <label for="last-name-input" class="control-label">Last Name</label>
                    <input type="text" class="form-control" id="last-name-input" name="ba_last_name"
                           placeholder="Last Name" value="{{ old('ba_last_name') }}">

                    @if ($errors->has('ba_last_name'))
                        <span class="help-block">
                            <strong>{{ $errors->first('ba_last_name') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="form-group{{ $errors->has('ba_country') ? ' has-error' : '' }}">
                    <label for="country-select" class="control-label">Country</label>
                    <select name="ba_country" class="form-control" id="country-select">
                        <option value=""></option>
                    </select>

                    @if ($errors->has('ba_country'))
                        <span class="help-block">
                            <strong>{{ $errors->first('ba_country') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="form-group{{ $errors->has('ba_city') ? ' has-error' : '' }}">
                    <label for="city-input" class="control-label">City</label>
                    <input type="text" class="form-control" id="city-input" name="ba_city"
                           placeholder="City" value="{{ old('ba_city') }}">

                    @if ($errors->has('ba_city'))
                        <span class="help-block">
                            <strong>{{ $errors->first('ba_city') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="form-group{{ $errors->has('ba_billing_address') ? ' has-error' : '' }}">
                    <label for="billing-address-input" class="control-label">Billing Address</label>
                    <input type="text" class="form-control" id="billing-address-input" name="ba_billing_address"
                           placeholder="Billing Address" value="{{ old('ba_billing_address') }}">

                    @if ($errors->has('ba_billing_address'))
                        <span class="help-block">
                            <strong>{{ $errors->first('ba_billing_address') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="form-group{{ $errors->has('ba_billing_address_second') ? ' has-error' : '' }}">
                    <label for="billing-address-second-input" class="control-label">Billing Address 2</label>
                    <input type="text" class="form-control" id="billing-address-second-input"
                           name="ba_billing_address_second"
                           placeholder="Billing Address 2" value="{{ old('ba_billing_address_second') }}">

                    @if ($errors->has('ba_billing_address_second'))
                        <span class="help-block">
                            <strong>{{ $errors->first('ba_billing_address_second') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="form-group{{ $errors->has('ba_postalcode') ? ' has-error' : '' }}">
                    <label for="postalcode-input" class="control-label">Postal Code</label>
                    <input type="text" class="form-control"
                           id="postalcode-input" name="ba_postalcode" placeholder="Postal Code"
                           value="{{ old('ba_postalcode') }}">

                    @if ($errors->has('ba_postalcode'))
                        <span class="help-block">
                            <strong>{{ $errors->first('ba_postalcode') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="form-group{{ $errors->has('ba_phone') ? ' has-error' : '' }}">
                    <label for="phone-input" class="control-label">Phone</label>
                    <input type="text" class="form-control"
                           id="phone-input" name="ba_phone" placeholder="Phone"
                           value="{{ old('ba_phone') }}">

                    @if ($errors->has('ba_phone'))
                        <span class="help-block">
                            <strong>{{ $errors->first('ba_phone') }}</strong>
                        </span>
                    @endif
                </div>

                @usercan('add', 'accounts.billing_addresses.store')
                <button type="submit" role="button" data-loading-text='{{ trans('loading.please_wait') }}'
                        class="btn btn-primary">
                    Save
                </button>
                @endusercan
            </form>
        </div>
    </div>
</div>

@section('script')
    @parent

    <script>
        $(function () {
            $('#form-add-billing-address').find('#country-select').select2({
                placeholder: "Select a country",
                data: countries
            }).val("{{ old('ba_country') }}").trigger('change');

            $('#form-add-billing-address').submit(function () {
                $(this).find(':submit').button('loading');
            });
        });
    </script>
@endsection