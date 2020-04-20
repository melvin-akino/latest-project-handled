<div class="modal fade" id="modal-manage-provider-accounts" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" name="form-manage-provider-account" id="form-manage-provider-account"
                  action="{{ route('provider_accounts.manage') }}"
                  method="POST">
                  {{ csrf_field() }}
                <input type="hidden" class="form-control" name="providerAccountId"
                                   id="providerAccountId"
                                   placeholder="ProviderAccountId">
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
                var formArray = form.serializeArray();
                var selectedProviderId;

                $.each(formArray, function() {
                    if (this.name == 'provider_id') {
                        selectedProviderId = this.value;
                    }
                });
                
                $.post(url, form.serialize(), function (response) {
                    if (response.data == 'success') {                        
                        swal('Provider Account', 'Provider account successfully saved', response.data).then(() => {
                            
                            if (form.find('input[name=providerAccountId]').val() == '') {
                                swal({
                                    title: 'Add more accounts?',
                                    type: 'info',
                                    showCancelButton: true,
                                    confirmButtonColor: '#3085d6',
                                    cancelButtonColor: '#d33',
                                    cancelButtonText: 'No',
                                    confirmButtonText: 'Yes'
                                })
                                .then((result) => {
                                    if (result.value) {
                                        clearErr(form);
                                        form.trigger('reset');
                                        form.find('input[name=providerAccountId]').val('');
                                        form.find('select[name=provider_id]').val(selectedProviderId);                                        
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
                clearErr(form);
                form.trigger('reset');
            });

            $('#form-manage-provider-account').submit(function(e) {
                e.preventDefault();
                $(this).find('input[name=username]').removeAttr("disabled");
                $(this).find('select[name=provider_id]').removeAttr("disabled");
                manageProviderAccount($(this));
            });           
        });
    </script>
@endsection
