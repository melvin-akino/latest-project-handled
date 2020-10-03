<div class="modal fade" id="modal-manage-error-message" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" name="form-manage-provider" id="form-manage-error-message"
                  action="{{ route('error_messages.manage') }}"
                  method="POST">
                  {{ csrf_field() }}
                  <input type="hidden" class="form-control" name="errorMessageId"
                                   id="errorMessageId"
                                   placeholder="errorMessageId">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Manage Error Message</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="add-currency-name-input" class="col-sm-3 control-label">Message</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="error"
                                   id="error"
                                   placeholder="Error Message">
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
            var manageErrorMessage = function (form) {
                var btn = form.find(':submit').button('loading');
                var url = form.prop('action');

                $.post(url, form.serialize(), function (response) {
                    
                    if (response.data == 'success') {
                        swal('Error Message', 'Error message successfully saved', response.data).then(() => {
                            $('#modal-manage-error-message').modal('toggle');
                            form.trigger('reset');
                            table.ajax.reload();
                        });
                    }
                    return;
                }).done(function () {
                    btn.button('reset');
                }).fail(function(xhr, status, error) {

                    assocErr(xhr.responseJSON.errors, form);

                    btn.button('reset');
                });
            };

            $("#modal-manage-error-message").on("hidden.bs.modal", function () {
                var form = $('#form-manage-error-message');
                form.find('input[name=errorMessageId]').val();                
                form.trigger('reset');
                clearErr(form);
            });

            $('#form-manage-error-message').submit(function(e) {
                e.preventDefault();
                manageErrorMessage($(this));
            });
        });
    </script>
@endsection
