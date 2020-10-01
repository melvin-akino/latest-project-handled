<div class="modal fade" id="modal-add-account" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" name="formaddaccount" id="formaddaccount" action="{{ route('providererror.create') }}" method="POST">
                {{ csrf_field() }}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Add Provider Error Message</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">

                        <div class="form-group">
                            <label for="add-email-input" class="col-sm-3 control-label">Error Message</label>

                            <div class="col-sm-8">
                                <input type="text" class="form-control" id="add-message-input" name="message" placeholder="Message" required="true">
                            </div>
                        </div>

                       <div class="form-group">
                            <label for="add-lname-input" class="col-sm-3 control-label">Error Message</label>
                            <div class="col-sm-8">
                                <select name="error_id" id="add-error-select" class="form-control" required="true">
                                    <option value="" disabled selected>Select Error message</option>
                                    @foreach($errormessages as $errormessage)
                                        <option value="{{ $errormessage->id }}">{{ $errormessage->error }}</li>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        

                        


                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                   
                    <button type="submit" role="button" data-loading-text='{{ trans('loading.please_wait') }}' class="btn btn-primary">Save changes</button>
                   
                </div>
            </form>
        </div>
    </div>
</div>
@section('script')
    @parent

    <script>
        $('[data-toggle="popover"]').popover();

        $('#formaddaccount').submit(function (e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find(':submit').button('loading');
            var url = form.prop('action');

            $.post(url, form.serialize(), function (data) {
                if (!data.errors) {
                    $('#modal-add-account').modal('hide');

                    window.location = '{{ route('providererror.index') }}';

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
