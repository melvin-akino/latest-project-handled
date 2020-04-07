<?php

namespace App\Http\Controllers\CRM\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Source, UserWallet, Currency};
use App\Models\CRM\WalletLedger;
class WalletController extends Controller
{
    
    public function dataTable(Request $request, UserWallet $wallet)
    {
        
        $query = $wallet->wallet_ledger()
            ->where(\DB::raw("(CASE
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
                    'sender' => $crm_transfer->user->email,
                    'amount' => $currency_symbol . ' ' . number_format($crm_transfer->transfer_amount, 8),
                    'created_at' => $ledger->created_at->toDayDateTimeString(),
                    'reason' => $crm_transfer->reason
                ]);
                break;
            
        }
        return response()->json(compact('html'));
    }
}
