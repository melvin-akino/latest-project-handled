<div class="modal fade" id="modal-user-password-reset" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" name="formuserpasswordreset" id="formuserpasswordreset" action="{{ route('accounts.change_pwd') }}" method="POST">
                {{ csrf_field() }}
                {{--{{ method_field('PUT') }}--}}
                <input type="hidden" name="user_id" value="" id="user-id-change-password">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Reset User Password</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="pass-reset-input" class="col-sm-3 control-label">New Password</label>

                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="pass-reset-input" name="password"
                                       placeholder="New Password">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="pass-confirmation-reset-input" class="col-sm-3 control-label">Repeat Password</label>

                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="pass-confirmation-reset-input"
                                       name="password_confirmation" placeholder="Repeat Password">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                   
                    <button type="submit" role="button" data-loading-text='{{ trans('loading.please_wait') }}' class="btn btn-primary">Reset Password</button>
                  
                </div>
            </form>
        </div>
    </div>
</div>