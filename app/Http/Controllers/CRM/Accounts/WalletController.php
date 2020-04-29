<?php

namespace App\Http\Controllers\CRM\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Source, UserWallet, Currency};
use App\Models\CRM\WalletLedger;
use Illuminate\Support\Facades\DB;
class WalletController extends Controller
{
    
    public function dataTable(Request $request, UserWallet $wallet)
    {
        
        $query = $wallet->wallet_ledger()
            ->where(DB::raw("(CASE
                    WHEN debit > 0 THEN credit >= 0
                    WHEN credit > 0 THEN debit >= 0
                END)"), true)
            ->with('userwallet', 'userwallet.account', 'userwallet.currency', 'source');
        return dataTable($request, $query, ['debit', 'credit', 'balance', 'created_at']);
        
    }

    public function ledgerSourceInfo(Request $request, WalletLedger $ledger)
    {
        $html = null; 
        switch (Source::getName($ledger->source_id)){
            case 'WITHDRAW':
            case 'DEPOSIT':
                    $crm_transfer = $ledger->crm_transfer_resource;
                    $currency_symbol = $crm_transfer->currency->code;

                    $html = (string)view('CRM.accounts.details.tabs.wallet.sources.crm', [
                        'sender'     => $crm_transfer->user->email,
                        'amount'     => $currency_symbol . ' ' . number_format($crm_transfer->transfer_amount, 2),
                        'created_at' => $ledger->created_at->toDayDateTimeString(),
                        'reason'     => $crm_transfer->reason
                    ]);

                break;

            case 'RETURN_STAKE':
            case 'RETURN_BET':
            case 'PLACE_BET':
                    $bet_data = $ledger->place_bet;
                    $bet_info = $bet_data->transaction_log;
                    $currency_symbol = $bet_data->currency->code;
                    
                    $html = (string)view('CRM.accounts.details.tabs.wallet.sources.bet', [
                        'amount'     => $currency_symbol . ' ' . number_format($bet_data->amount, 2),
                        'bet_action' => $bet_data->reason ,
                        'bet_status' => $bet_info->status,
                        'created_at' => $ledger->created_at->toDayDateTimeString(),
                        'game_info'  => $bet_info->bet_selection
                    ]);

                break;

            case 'BET_LOSE':
            case 'BET_HALF_WIN':
            case 'BET_HALF_LOSE':
            case 'BET_WIN':
                    $bet_data = $ledger->place_bet;
                    $bet_info = $bet_data->transaction_log;
                    $currency_symbol = $bet_data->currency->code;
                    
                    $html = (string)view('CRM.accounts.details.tabs.wallet.sources.betresult', [
                        'amount'       => $currency_symbol . ' ' . number_format($bet_data->amount, 2),
                        'bet_status'   => $bet_info->status,
                        'protif_loss'  => number_format($bet_info->profit_loss, 2),
                        'created_at'   => $ledger->created_at->toDayDateTimeString(),
                        'game_info'    => $bet_info->bet_selection,
                    ]);

                break;

            case 'REGISTRATION':
                  $html = (string)view('CRM.accounts.details.tabs.wallet.sources.register', [
                       
                        'created_at'  => $ledger->created_at->toDayDateTimeString(),
                    ]);

                break;
            
        }
        return response()->json(compact('html'));
    }
}
