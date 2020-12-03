<?php

namespace App\Http\Controllers\CRM\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Source, UserWallet, WalletLedger};
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

        switch (Source::getName($ledger->source_id)) {
            case 'WITHDRAW':
            case 'DEPOSIT':
                $crmTransfer    = $ledger->crm_transfer_resource;
                $currencySymbol = $crmTransfer->currency->code;

                $html = (string) view('CRM.accounts.details.tabs.wallet.sources.crm', [
                    'sender'     => $crmTransfer->user->email,
                    'amount'     => $currencySymbol . ' ' . number_format($crmTransfer->transfer_amount, 2),
                    'created_at' => $ledger->created_at->toDayDateTimeString(),
                    'reason'     => $crmTransfer->reason
                ]);

                break;

            case 'RETURN_STAKE':
            case 'RETURN_BET':
            case 'PLACE_BET':
                $betData        = $ledger->place_bet;
                $betInfo        = $betData->transaction_log;
                $currencySymbol = $betData->currency->code;

                $html = (string) view('CRM.accounts.details.tabs.wallet.sources.bet', [
                    'amount'     => $currencySymbol . ' ' . number_format($betData->amount, 2),
                    'bet_action' => $betData->reason,
                    'bet_status' => $betInfo->status,
                    'created_at' => $ledger->created_at->toDayDateTimeString(),
                    'game_info'  => $betInfo->bet_selection
                ]);

                break;

            case 'BET_LOSE':
            case 'BET_HALF_WIN':
            case 'BET_HALF_LOSE':
            case 'BET_WIN':
                $betData         = $ledger->place_bet;
                $betInfo         = $betData->transaction_log;
                $currency_symbol = $betData->currency->code;

                $html = (string) view('CRM.accounts.details.tabs.wallet.sources.betresult', [
                    'amount'      => $currency_symbol . ' ' . number_format($betData->amount, 2),
                    'bet_status'  => $betInfo->status,
                    'protif_loss' => number_format($betInfo->profit_loss, 2),
                    'created_at'  => $ledger->created_at->toDayDateTimeString(),
                    'game_info'   => $betInfo->bet_selection,
                ]);

                break;

            case 'REGISTRATION':
                $html = (string) view('CRM.accounts.details.tabs.wallet.sources.register', [

                    'created_at' => $ledger->created_at->toDayDateTimeString(),
                ]);

                break;

        }
        return response()->json(compact('html'));
    }
}
