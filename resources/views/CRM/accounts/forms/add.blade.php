<div class="modal fade" id="modal-add-account" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" name="formaddaccount" id="formaddaccount" action="{{ route('accounts.add') }}" method="POST">
                {{ csrf_field() }}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Add Users</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">

                        <div class="form-group">
                            <label for="add-email-input" class="col-sm-3 control-label">E-mail Address</label>

                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="add-email-input" name="email" placeholder="E-mail Address">
                            </div>
                        </div>

                        {{--
                        <div class="form-group">
                            <label for="add-lname-input" class="col-sm-3 control-label">Username</label>

                            <div class="col-sm-6">

                                <input type="text" class="form-control" id="add-username-input" name="username"
                                       placeholder="">
                            </div>
                            <div class="col-sm-2">
                                <button type="button" id="generate_username" class="btn btn-success" data-toggle="popover" data-trigger="hover" data-content="Generate username"><i class="fa fa-refresh"></i></button>
                            </div>
                        </div>
                        --}}

                        <div class="form-group">
                            <label for="add-lname-input" class="col-sm-3 control-label">Password</label>

                            <div class="col-sm-6">

                                <input type="text" class="form-control" id="add-password-input" name="password"
                                       placeholder="">
                            </div>
                            <div class="col-sm-2">
                                <button type="button" class="btn btn-success" id="generate_pass" data-toggle="popover" data-trigger="hover" data-content="Generate password"><i class="fa fa-refresh"></i></button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="add-fname-input" class="col-sm-3 control-label">First Name</label>

                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="add-fname-input" name="first_name"
                                       placeholder="First name">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="add-lname-input" class="col-sm-3 control-label">Last Name</label>

                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="add-lname-input" name="last_name"
                                       placeholder="Last name">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="add-lname-input" class="col-sm-3 control-label">Currency</label>
                            <div class="col-sm-8">
                                <select name="currency_id" id="add-currency-select" class="form-control">
                                    <option value="" disabled selected>Select to currency</option>
                                    @foreach($currencies as $currency)
                                        <option value="{{ $currency->id }}">{{ $currency->name . ' (' . $currency->code . ')' }}</li>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{--<div class="form-group">--}}
                            {{--<label for="add-status-input" class="col-sm-3 control-label">Status</label>--}}
                            {{--<div class="col-sm-8">--}}
                                {{--<select class="form-control" name="status_id" id="add-status-input">--}}
                                    {{--@foreach($statuses as $status)--}}
                                        {{--<option value="{{ $status->status_id }}">{{ $status->status_name }}</option>--}}
                                    {{--@endforeach--}}
                                {{--</select>--}}
                            {{--</div>--}}
                        {{--</div>--}}


                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                    {{-- @usercan('add', 'assignuser.add') --}}
                    <button type="submit" role="button" data-loading-text='{{ trans('loading.please_wait') }}' class="btn btn-primary">Save changes</button>
                    {{-- @endusercan --}}
                </div>
            </form>
        </div>
    </div>
</div>
@section('script')
    @parent

    <script>
        $('[data-toggle="popover"]').popover();

        $("#add-username-input, #add-password-input").keypress(function(e){
            var keyCode = e.which;

            if ( !( (keyCode >= 48 && keyCode <= 57) ||(keyCode >= 65 && keyCode <= 90) || (keyCode >= 97 && keyCode <= 122) )) {
                e.preventDefault();
            }

            if(keyCode === 32 || keyCode === 8){
                return false;
            }
        });

        $('#formaddaccount').submit(function (e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find(':submit').button('loading');
            var url = form.prop('action');

            $.post(url, form.serialize(), function (data) {
                if (!data.errors) {
                    $('#modal-add-account').modal('hide');

                    window.location = '{{ route('accounts.index') }}';
//                    if (data.swal) {
//                        swal({
//                            title: data.swal.title,
//                            html: data.swal.html,
//                            type: data.swal.type
//                        });
//                    }
//                    return;
                };

            }).fail(function(xhr, status, error) {
                assocErr(xhr.responseJSON.errors, form);
                btn.button('reset');

            }).done(function () {
                btn.button('reset');
            })
        });

    </script>

@endsection
