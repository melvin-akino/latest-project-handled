<div class="modal fade" id="modal-edit-message" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" name="form-edit-message" id="form-edit-message"
                  action="{{ route('providererror.update') }}"
                  method="POST">
                {{ csrf_field() }}
              
               
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Edit Error Message</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit-edit_message-input" class="col-sm-3 control-label">Provider Error Message</label>

                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="edit_message"
                                   id="edit-edit_message-input" required="true"
                                   placeholder="Name">
                        </div>
                    </div>

                

                    <div class="form-group">
                            <label for="add-lname-input" class="col-sm-3 control-label">Error Message</label>
                            <div class="col-sm-8">
                                <select name="error_id" id="error_id" class="form-control" required="true">
                                    <option value="" disabled selected>Select Error message</option>
                                    @foreach($errormessages as $errormessage)
                                        <option value="{{ $errormessage->id }}">{{ $errormessage->error }}</li>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                  

                  
                    <input type="hidden" name="confirm">
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

@section('script')
    @parent

    <script>
        $(function () {
           
            $("#modal-edit-message").on("hidden.bs.modal", function () {
                var form = $('#form-edit-message');
                form.removeAttr('data-message-id');
                form.trigger('reset');
                clearErr(form);
            });
          

            $('#form-edit-message').submit(function (e) {
                e.preventDefault();
                var form = $(this);
                var btn = form.find(':submit').button('loading');
                var url = form.prop('action');

                $.post(url, form.serialize(), function (response) {
                    console.log(response);
                    if (response.status == '{{ config('response.type.success') }}') {
                        swal('Provider Error', 'Saved Succesfully!', response.status).then(() => {
                            window.location = '{{ route('providererror.index') }}'; 
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
