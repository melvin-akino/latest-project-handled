<div class="modal fade" id="modal-manage-provider" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" name="form-manage-provider" id="form-manage-provider"
                  action="{{ route('providers.manage') }}"
                  method="POST">
                  {{ csrf_field() }}
                  <input type="hidden" class="form-control" name="providerId"
                                   id="providerId"
                                   placeholder="ProviderId">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Manage Provider</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="add-currency-name-input" class="col-sm-3 control-label">Name</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="name"
                                   id="name"
                                   placeholder="Provider Name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="add-parent-category-id-input" class="col-sm-3 control-label">Alias</label>

                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="alias"
                                   id="alias"
                                   placeholder="Alias that will appear on betslip">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="add-sort-input" class="col-sm-3 control-label">Percentage</label>

                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="percentage"
                                   id="percentage"
                                   placeholder="Percentage of max stake on betslip">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="add-status-id-input" class="col-sm-3 control-label">Enabled</label>

                        <div class="col-sm-8">
                            <div class="form-group">
                              <div class="radio">
                                <label>
                                  <input type="radio" name="is_enabled" id="is_enabled1" value="true" checked>Yes
                                </label>
                              </div>
                              <div class="radio">
                                <label>
                                  <input type="radio" name="is_enabled" id="is_enabled2" value="false">No                                  
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
            var manageProvider = function (form) {
                var btn = form.find(':submit').button('loading');
                var url = form.prop('action');
                
                $.post(url, form.serialize(), function (response) {
                    if (response.data == 'success') {
                        swal('Provider', 'Provider successfully saved', response.data).then(() => {
                            location.href = 'providers';
                        });
                    } 
                    return;
                }).done(function () {
                    btn.button('reset');
                });
            };

            $("#modal-manage-provider").on("hidden.bs.modal", function () {
                var form = $('#form-manage-provider');
                form.trigger('reset');
            });

            $.validator.setDefaults({
                submitHandler: function () {
                    var form = $('#form-manage-provider');
                    manageProvider(form);
                }
            });
            $.validator.addMethod("isUnique", function(value, element) {
                var providerLen = providerList.length;
                var unique = true;
                var isNew = $('input[name="providerId"]').val();

                for(var i = 0; i<providerLen; i++){
                    var name = providerList[i]['name'];
                    if (isNew == '' && name == value) {
                        unique = false;
                        break;
                    }
                }
                return this.optional( element ) || unique;
            }, 'Name already taken.');

            $.validator.addMethod("isUniqueAlias", function(value, element) {
                var providerLen = providerList.length;
                var unique = true;
                var isNew = $('input[name="providerId"]').val();

                for(var i = 0; i<providerLen; i++){
                    var name = providerList[i]['alias'];
                    if (isNew == '' && name == value) {
                        unique = false;
                        break;
                    }
                }
                return this.optional( element ) || unique;
            }, 'Name already taken.');

            $('#form-manage-provider').validate({
                rules: {
                    name: {
                        required: true,
                        isUnique: true
                    },
                    alias: {
                        required: true,
                        isUniqueAlias: true
                    },
                    percentage: {
                        required: true,
                        digits: true

                    },
                },
                messages: {
                    name: {
                        required: "Please enter a provider name",
                    },
                    alias: {
                        required: "Please provide an alias"
                    },
                    percentage: { 
                        required: "Percentage is required",
                        digits: "Please enter a valid percentage"
                    }

                },
                errorElement: 'span',
                    errorPlacement: function (error, element) {
                        error.addClass('invalid-feedback');
                        element.closest('div').append(error);
                    },
                    highlight: function (element, errorClass, validClass) {
                        $(element).addClass('is-invalid');
                    },
                    unhighlight: function (element, errorClass, validClass) {
                        $(element).removeClass('is-invalid');
                    }
            });
        });
    </script>
@endsection
