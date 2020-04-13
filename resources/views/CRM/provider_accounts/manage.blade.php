<div class="modal fade" id="modal-manage-provider-accounts" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" name="form-manage-provider-account" id="form-manage-provider-account"
                  action="{{ route('provider_accounts.manage') }}"
                  method="POST">
                  {{ csrf_field() }}
                  <input type="hidden" class="form-control" name="providerAccountId"
                                   id="providerAccountId"
                                   placeholder="ProviderId">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Manage Provider Account</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="add-currency-name-input" class="col-sm-3 control-label">Username</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="username"
                                   id="username"
                                   placeholder="Provider account username">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="add-parent-category-id-input" class="col-sm-3 control-label">Password</label>

                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="password"
                                   id="password"
                                   placeholder="Provider account password">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="add-sort-input" class="col-sm-3 control-label">Provider</label>

                        <div class="col-sm-8">
                            <select id="provider_id" class="form-control" name="provider_id">
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="add-sort-input" class="col-sm-3 control-label">Type</label>

                        <div class="col-sm-8">
                            <select id="account_type" class="form-control" name="account_type">
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="add-parent-category-id-input" class="col-sm-3 control-label">Percentage</label>

                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="pa_percentage"
                                   id="pa_percentage"
                                   placeholder="Punter percentage">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="add-status-id-input" class="col-sm-3 control-label">Enabled</label>

                        <div class="col-sm-8">
                            <div class="form-group">
                              <div class="radio">
                                <label>
                                  <input type="radio" name="pa_is_enabled" id="pa_is_enabled1" value="1" checked>Yes
                                </label>
                              </div>
                              <div class="radio">
                                <label>
                                  <input type="radio" name="pa_is_enabled" id="pa_is_enabled2" value="0">No                                  
                                </label>
                              </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="add-status-id-input" class="col-sm-3 control-label">Idle</label>

                        <div class="col-sm-8">
                            <div class="form-group">
                              <div class="radio">
                                <label>
                                  <input type="radio" name="is_idle" id="is_idle1" value="1" checked>Yes
                                </label>
                              </div>
                              <div class="radio">
                                <label>
                                  <input type="radio" name="is_idle" id="is_idle2" value="0">No                                  
                                </label>
                              </div>
                            </div>
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
          var manageProviderAccount = function (form) {
                var btn = form.find(':submit').button('loading');
                var url = form.prop('action');
                
                $.post(url, form.serialize(), function (response) {
                    if (response.data == 'success') {

                        swal('Provider Account', 'Provider account successfully saved', response.data).then(() => {
                            
                            if (form.find('input[name=providerAccountId]').val() == '') {
                                swal({
                                    title: 'Add more?',
                                    type: 'info',
                                    showCancelButton: true,
                                    confirmButtonColor: '#3085d6',
                                    cancelButtonColor: '#d33',
                                    confirmButtonText: 'Yes'
                                })
                                .then((result) => {
                                    if (result.value) {
                                        form.trigger('reset');
                                    }
                                    else {
                                        $('#modal-manage-provider-accounts').modal('toggle');
                                    }
                                });
                            }
                            else {
                                $('#modal-manage-provider-accounts').modal('toggle');
                            }

                            childTable.ajax.reload();
                            
                        });
                    } 
                    return;
                }).done(function () {
                    btn.button('reset');
                }).fail(function(xhr, status, error) {
                    // error handling
                    assocErr(xhr.responseJSON.errors, form);

                    btn.button('reset');
                });
            };

            $("#modal-manage-provider-accounts").on("hidden.bs.modal", function () {
                var form = $('#form-manage-provider-account');
                form.trigger('reset');
            });

            $.validator.setDefaults({
                submitHandler: function () {
                    var form = $('#form-manage-provider-account');
                    manageProviderAccount(form);
                }
            });
            $.validator.addMethod("isUniqueUsername", function(value, element) {
                var providerAccountLen = providerAccountList.length;
                var unique = true;
                var isNew = $('input[name="providerAccountId"]').val();

                for(var i = 0; i<providerAccountLen; i++){
                    var name = providerAccountList[i]['username'];

                    console.log('name: ' + name + ' | value: ' + value);
                    if (isNew == '' && name == value) {
                        unique = false;
                        break;
                    }
                }
                return this.optional( element ) || unique;
            }, 'Username already taken.');

            $('#form-manage-provider-account').validate({
                rules: {
                    username: {
                        required: true,
                        isUniqueUsername: true
                    },
                    password: {
                        required: true,
                    },
                    pa_percentage: {
                        required: true,
                        digits: true

                    },
                },
                messages: {
                    username: {
                        required: "Please enter a provider account username"
                    },
                    password: {
                        required: "Please provide a password"
                    },
                    pa_percentage: { 
                        required: "Percentage is required",
                        digits: "Please enter a valid percentage"
                    }

                },
                errorElement: 'span',
                    errorPlacement: function (error, element) {
                        error.addClass('invalid-feedback');
                        element.closest('div').addClass('has-error');
                        element.closest('div').append(error);
                    },
                    highlight: function (element, errorClass, validClass) {
                        $(element).addClass('is-invalid');
                    },
                    unhighlight: function (element, errorClass, validClass) {
                        $(element).closest('div').removeClass('has-error');
                        $(element).removeClass('is-invalid');
                    }
            });
          });
        </script>
        @endsection
