<div class="modal fade" id="modal-transfer" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" name="form-transfer" id="form-transfer" action="/admin/wallet/transfer" method="POST">
                {{ csrf_field() }}
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">{{ $page_title }} funds : <strong id="email"></strong></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="user-id-input" name="user_id">
                    <!-- <input type="text" id="user-id-input" name="currency_id"> -->
                    <div class="form-group">
                        <label for="currency-id-select" class="col-sm-2 control-label">Currency</label>

                        <div class="col-sm-9">
                            <select class="form-control" id="currency-id-select" name="currency_id" data-width="100%" disabled>
                                @if($in_app_currencies->count())

                                        @foreach($in_app_currencies as $currency)
                                            <option value="{{ $currency->id }}">{{ $currency->name }}</option>
                                        @endforeach
                                @endif

                            </select>
                        </div>
                    </div>
                    <div class="form-group hidden">
                        <label for="transfer-amount-input" class="col-sm-2 control-label">Mode</label>

                        <div class="col-sm-9 radio">
                            <label><input type="radio" name="mode" value="add" @if($page_title == 'Deposit') checked @endif>Deposit</label> <br>
                            <label><input type="radio" name="mode" value="deduct" @if($page_title == 'Withdraw') checked @endif >Withdraw</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="transfer-amount-input" class="col-sm-2 control-label">Amount</label>

                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="transfer-amount-input" step="0.01" name="transfer_amount"
                                   placeholder="Enter amount to transfer">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="reason-textarea" class="col-sm-2 control-label">Reason</label>

                        <div class="col-sm-9">
                            <textarea class="form-control" name="reason" id="reason-textarea" rows="3"
                                      placeholder="Enter reason" style="resize:none;"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancel</button>
                    
                    <button type="submit" role="button" data-loading-text='{{ trans('loading.please_wait') }}' class="btn btn-primary">
                        {{ $page_title }}
                    </button>
                   
                </div>
            </form>
        </div>
    </div>
</div>

@section('script')
    @parent

    <!-- ClubNine -->
    <script src="{{ asset("CRM/Capital7-1.0.0/js/form-validation.js") }}"></script>

    <script>
        $(function () {
            $("#modal-transfer").on("hidden.bs.modal", function () {
                var form = $('#form-transfer');
                form.find('#email').empty();
                form.find('[name=user_id]').val('');
                form.find('[name=currency_id]').val('').trigger('change');
                form.trigger('reset');
                clearErr(form);
            });

            $('#form-transfer').submit(function (e) {
                e.preventDefault();
                var form = $(this);
                var btn = form.find(':submit').button('loading');
                var url = form.prop('action');

                $('#currency-id-select').prop('disabled', false);

                $.post(url, form.serialize(), function (data) {                    
                    if (!data.errors) {
                        $('#modal-transfer').modal('hide');

                        if (data.swal) {
                            swal({
                                title: data.swal.title,
                                html: data.swal.html,
                                type: data.swal.type
                                
                            }).then(function(){
                                      window.location.reload();
                            });
                        }
                        $('#currency-id-select').prop('disabled', true);
                        //location.reload();
                        return;
                    }
                    assocErr(data.errors, form);

                }).fail(function(xhr, status, error) {
                    $('#currency-id-select').prop('disabled', true);
                    assocErr(xhr.responseJSON.errors, form);
                    btn.button('reset');
                }).done(function () {
                    $('#currency-id-select').prop('disabled', true);
                    btn.button('reset');
                })
            });

            $('#currency-id-select').select2({
                placeholder: "Select currency"
            });
        });
    </script>

@endsection