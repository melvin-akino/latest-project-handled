<div id="personal" class="tab-pane fade">
    <div class="row">
        <div class="col-md-4">
            <form name="formeditaccount" id="formeditaccount" action="{{ route('accounts.update', $account) }}#personal"
                  method="POST">
                {{ csrf_field() }}
                {{ method_field('PUT') }}

                <div class="form-group">
                    <label for="username-input" class="control-label">Display Name</label>
                    <input type="text" class="form-control" id="username-input" name="name"
                           placeholder="Display Name" disabled value="{{ old('name', $account->name) }}">
                </div>

                <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                    <label for="email-input" class="control-label">Email</label>
                    <input readonly type="text" class="form-control" id="email-input"
                           name="email" placeholder="Email" value="{{ old('email', $account->email) }}">

                    @if ($errors->has('email'))
                        <span class="help-block">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif
                </div>                
                <div class="form-group">
                    <label for="modal-edit-status" class="control-label">VIP </label>
                    <select class="form-control" id="is_vip" name="is_vip">
                       
                        <option value="false" @if ($account->is_vip == false) SELECTED @endif >No</option>
                        <option value="true" @if ($account->is_vip == true) SELECTED @endif >Yes</option>
                      

                        </select>
                </div>                
                 
                <div class="form-group">
                    <label for="modal-edit-status" class="control-label">Status </label>
                    <select class="form-control" id="status_select" name="status">
                       
                        <option value="1" @if ($account->status == 1) SELECTED @endif >Active</option>
                        <option value="0" @if ($account->status == 0) SELECTED @endif >Inactive</option>
                      

                        </select>
                </div>



                <div class="form-group{{ $errors->has('firstname') ? ' has-error' : '' }}">
                    <label for="first-name-input" class="control-label">First Name</label>
                    <input type="text" class="form-control" id="first-name-input" name="firstname"
                           placeholder="First Name" value="{{ old('firstname', $account->firstname) }}">

                    @if ($errors->has('firstname'))
                        <span class="help-block">
                            <strong>{{ $errors->first('firstname') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="form-group{{ $errors->has('lastname') ? ' has-error' : '' }}">
                    <label for="last-name-input" class="control-label">Last Name</label>
                    <input type="text" class="form-control" id="last-name-input" name="lastname"
                           placeholder="Last Name" value="{{ old('lastname', $account->lastname) }}">

                    @if ($errors->has('lastname'))
                        <span class="help-block">
                            <strong>{{ $errors->first('lastname') }}</strong>
                        </span>
                    @endif
                </div>
                 <div class="form-group{{ $errors->has('phone') ? ' has-error' : '' }}">
                    <label for="phone-input" class="control-label">Mobile</label>
                    <input type="text" class="form-control" id="phone-input" name="phone"
                           placeholder="Mobile" value="{{ old('phone', $account->phone) }}">

                    @if ($errors->has('phone'))
                        <span class="help-block">
                            <strong>{{ $errors->first('lastname') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="form-group{{ $errors->has('birthdate') ? ' has-error' : '' }}">
                    <label for="birth_date-input" class="control-label">Date of Birth</label>
                    <input type="text" class="form-control" data-date-end-date="-18y" id="birth-date-input" name="birth_date" placeholder="Select Date of Birth" value="{{ old('birthdate', $account->birthdate) }}">

                    @if ($errors->has('birthdate'))
                        <span class="help-block">
                            <strong>{{ $errors->first('birthdate') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="form-group">
                    <label for="modal-edit-status" class="control-label">Country </label>
                    <select class="form-control" id="country_select" name="country_id">
                        @foreach($countries as $country)
                        <option value="{{ $country->id }}" @if ($account->country_id == $country->id) SELECTED @endif >{{ $country->country_name }}</option>
                        @endforeach

                        </select>
                </div>
                <div class="form-group{{ $errors->has('state') ? ' has-error' : '' }}">
                    <label for="state-input" class="control-label">State</label>
                    <input type="text" class="form-control"  id="state-input" name="state" placeholder="State" value="{{ old('state', $account->state) }}">

                    @if ($errors->has('state'))
                        <span class="help-block">
                            <strong>{{ $errors->first('state') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group{{ $errors->has('city') ? ' has-error' : '' }}">
                    <label for="city-input" class="control-label">City</label>
                    <input type="text" class="form-control"  id="city-input" name="city" placeholder="City" value="{{ old('city', $account->city) }}">

                    @if ($errors->has('city'))
                        <span class="help-block">
                            <strong>{{ $errors->first('city') }}</strong>
                        </span>
                    @endif
                </div>
                 <div class="form-group{{ $errors->has('address') ? ' has-error' : '' }}">
                    <label for="address-input" class="control-label">Address</label>
                    <textarea name="address" id="address-input" class = "form-control" rows = "3"> {{ old('address', $account->address) }}</textarea>
                      
                    @if ($errors->has('address'))
                        <span class="help-block">
                            <strong>{{ $errors->first('address') }}</strong>
                        </span>
                    @endif
                </div>
                  <div class="form-group{{ $errors->has('postcode') ? ' has-error' : '' }}">
                    <label for="postcode-input" class="control-label">Postal Code</label>
                    <input type="text" class="form-control"  id="postcode-input" name="postcode" placeholder="Postcode" value="{{ old('postcode', $account->postcode) }}">

                    @if ($errors->has('postcode'))
                        <span class="help-block">
                            <strong>{{ $errors->first('postcode') }}</strong>
                        </span>
                    @endif
                </div>


                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group {{ $errors->has('password') ? ' has-error' : '' }}">
                            <label for="add-password-input" class="control-label">Password</label>
                            <div class="row">


                                <div class="col-md-8">
                                    <input type="password" disabled class="form-control" id="add-password-input" name="password"
                                           placeholder="" value="">
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-success hidden" id="generate_pass" data-toggle="popover" data-trigger="hover" data-content="Generate password"><i class="fa fa-refresh"></i></button>
                                </div>
                            </div>

                            <div class="checkbox">
                                <label><input type="checkbox" id="change-password" value="">Change Password</label>
                            </div>

                            @if ($errors->has('password'))
                                <span class="help-block">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                            @endif

                        </div>

                    </div>
                </div>
               

                {{--<div class="form-group">--}}
                    {{--<button type="button" id="reset-password-button" role="button" class="btn btn-warning">Reset Password</button>--}}
                {{--</div>--}}

                {{--<div class="form-group">--}}
                    {{--<p><br></p>--}}
                {{--</div>--}}

             
                <button type="submit" role="button" data-loading-text='{{ trans('loading.please_wait') }}' class="btn btn-primary">Save</button>
              
            </form>
        </div>
    </div>
</div>

@include('CRM.accounts.forms.password_reset')

@section('scripts')
    @parent
    <script src="{{ asset('CRM/AdminLTE-2.4.2/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
@endsection

@section('styles')
    @parent
    <link rel="stylesheet" type="text/css" href="{{ asset('CRM/AdminLTE-2.4.2/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker3.css') }}">
@endsection

@section('script')
    @parent
    <script src="{{ asset('CRM/Capital7-1.0.0/js/form-validation.js') }}"></script>

    <script>
//    var loadThumbnail = document.getElementById("img-thumbnail").complete;
//    var imgsrc= document.getElementById("img-thumbnail").src;
//    function loadImg(imgholder){
//       $('#img-thumbnail').attr('src',imgsrc);
//       if( $('#img-thumbnail').complete) $('#img-thumbnail').trigger('load');
//    }
        $(function () {

            var generatePassword = function(numLc, numUc, numDigits, numSpecial) {
                numLc = numLc || 4;
                numUc = numUc || 4;
                numDigits = numDigits || 4;
                numSpecial = numSpecial || 2;


                var lcLetters = 'abcdefghijklmnopqrstuvwxyz';
                var ucLetters = lcLetters.toUpperCase();
                var numbers = '0123456789';
                var special = '&@!#+';

                var getRand = function(values) {
                    return values.charAt(Math.floor(Math.random() * values.length));
                }

                //+ Jonas Raoni Soares Silva
                //@ http://jsfromhell.com/array/shuffle [v1.0]
                function shuffle(o){ //v1.0
                    for(var j, x, i = o.length; i; j = Math.floor(Math.random() * i), x = o[--i], o[i] = o[j], o[j] = x);
                    return o;
                };

                var pass = [];
                for(var i = 0; i < numLc; ++i) { pass.push(getRand(lcLetters)) }
                for(var i = 0; i < numUc; ++i) { pass.push(getRand(ucLetters)) }
                for(var i = 0; i < numDigits; ++i) { pass.push(getRand(numbers)) }
                for(var i = 0; i < numSpecial; ++i) { pass.push(getRand(special)) }

                return shuffle(pass).join('');
            };

            $('#generate_pass').click(function(){

                var randPassword = generatePassword(3,3,1,1);

                $('#add-password-input').val(randPassword);
            });

            $('#change-password').change(function(){
                if (this.checked) {
                    $('#add-password-input').attr('type', 'text');
                    $('#add-password-input').prop('disabled', false);
                    $('#add-password-input').prop('required', true);
                    $('#generate_pass').removeClass('hidden')
                } else {
                    $('#add-password-input').val('');
                    $('#add-password-input').attr('type', 'password');
                    $('#add-password-input').prop('disabled', true);
                    $('#add-password-input').prop('required', false);
                    $('#generate_pass').addClass('hidden')

                }
            });

            $('#reset-password-button').click(function (e) {
                var modal = $('#modal-user-password-reset');
                var form = modal.find('form');
                $('#user-id-change-password').val('{{ $account->id }}');
//                form.attr('action', form.data('temp-action') + "/" + $(this).data('id') + form.data('append-action'));
                modal.modal('show');
            });

            $("#modal-user-password-reset").on("hidden.bs.modal", function () {
                var form = $('#formuserpasswordreset');
                form.trigger('reset');
                clearErr(form);
            });

            $('#formuserpasswordreset').submit(function (e) {
                e.preventDefault();
                var form = $(this);
                var btn = form.find(':submit').button('loading');

                $.ajax({
                    type: form.prop('method'),
                    url: form.prop('action'),
                    data: form.serialize(),
                    success: function (response) {
//                            location.reload();

                        swal({
                            'title' : 'Success',
                            'html' : 'Password has changed',
                            'type' : 'success'
                        })
                    }
                }).fail(function(xhr, status, error) {
                    assocErr(xhr.responseJSON.errors, form);
                    btn.button('reset');

                }).done(function () {
                    btn.button('reset');
                });
            });

            var country_text = '{{ $account->country??'' }}';
            if(country_text != ''){
                $("#country_select").val(country_text);
            }


            $('#birth-date-input').datepicker({
                clearBtn: true,
                format: "MM d, yyyy"
            });

            $('#formeditaccount').find('#country').select2({
                placeholder: "Select a country",
                data: countries
            }).val("{{ old('country', $account->country) }}").trigger('change');

            $('#formeditaccount').submit(function () {
                $(this).find(':submit').button('loading');
            });
//            if(loadThumbnail == false){
//                setTimeout(function(){ loadImg(); }, 2000);
//                }

            $('#currency-select').val({{ $account->currency_id }});

            $('#add-password-input').keypress(function (e) {
                var keyCode = e.which;

                if ( !( (keyCode >= 48 && keyCode <= 57) ||(keyCode >= 65 && keyCode <= 90) || (keyCode >= 97 && keyCode <= 122) )) {
                    e.preventDefault();
                }

                if(keyCode === 32 || keyCode === 8){
                    return false;
                }
            });
        });

    </script>
@endsection
