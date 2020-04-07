<div id="wallet" class="tab-pane fade">
    @if($account->wallet()->count())
        <div class="row">
            <div class="col-md-4">
                @foreach($account->wallet as $wallet)
                <div class="panel panel-default wallet-currency-entry">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="pull-left">
                                    <h3>
                                        <small><i class="ion ion-ios-pricetags-outline"></i> {{ $wallet->currency->name }}<br>
                                            
                                    </h3>
                                </div>
                                <div class="pull-right">
                                    <h3>
                                        <code>{{ number_format($wallet->balance, 2) }}</code> <small class="text-muted">{{ $wallet->currency->currency }}</small>
                                    </h3>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="pull-right">
                                    
                                    <button type="button" class="btn btn-link more-details-link" data-loading-text='{{ trans('loading.default') }}' data-wallet-id="{{ $wallet->id }}" style="padding: 0;">
                                        <i class="ion ion-android-checkbox-outline"></i> more details
                                    </button>
                                  
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    @else
        <p class="text-muted">{{ trans('validation.custom.wallet.empty', ['name' => $account->name()]) }}</p>
    @endif
</div>

@include('CRM.accounts.details.tabs.wallet.transaction')