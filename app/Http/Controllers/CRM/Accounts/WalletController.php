<?php

namespace App\Http\Controllers\CRM\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Source, UserWallet};
use App\Models\CRM\WalletLedger;
class WalletController extends Controller
{
    
    public function dataTable(Request $request, Wallet $wallet)
    {
        $query = $wallet->wallet_ledger()
            ->where(\DB::raw("(CASE
                    WHEN debit > 0 THEN credit >= 0
                    WHEN credit > 0 THEN debit >= 0
                END)"), true)
            ->with('wallet', 'wallet.account', 'wallet.currency', 'source');
        return dataTable($request, $query, ['debit', 'credit', 'balance', 'created_at']);
    }

    public function ledgerSourceInfo(Request $request, Wallet_ledger $ledger)
    {
        $html = null;

        switch ($ledger->source_id) {
            case Source::getIdByName('Charges'):
                $transaction_detail = $ledger->transaction_resource->transaction_detail;
                $currency_symbol = $transaction_detail->charge->currency->currency_symbol;

                $price = is_null($transaction_detail->price) ? 0 : $transaction_detail->price;
                $values = is_null($transaction_detail->values) ? 0 : $transaction_detail->values;

                $html = (string)view('CRM.accounts.details.tabs.wallet.sources.charges', [
                    'charge_description' => $transaction_detail->charge->description,
                    'transaction_detail_price' => $currency_symbol . ' ' . number_format($price, 8),
                    'transaction_detail_values' => $currency_symbol . ' ' . number_format($values, 8),
                    'created_at' => $ledger->created_at->toDayDateTimeString()
                ]);
                break;
            case Source::getIdByName('Membership'):
                $membership = $ledger->membership_resource;
                $currency_symbol = $membership->currency->currency_symbol;

                $html = (string)view('CRM.accounts.details.tabs.wallet.sources.membership', [
                    'membership_name' => $membership->name,
                    'membership_price' => $currency_symbol . ' ' . number_format($membership->price, 8),
                    'created_at' => $ledger->created_at->toDayDateTimeString()
                ]);
                break;
            case Source::getIdByName('CRM'):
                $crm_transfer = $ledger->crm_transfer_resource;
                $currency_symbol = $crm_transfer->currency->currency_symbol;

                $html = (string)view('CRM.accounts.details.tabs.wallet.sources.crm', [
                    'sender' => $crm_transfer->user->email,
                    'amount' => $currency_symbol . ' ' . number_format($crm_transfer->transfer_amount, 8),
                    'created_at' => $ledger->created_at->toDayDateTimeString(),
                    'reason' => $crm_transfer->reason
                ]);
                break;
            case Source::getIdByName('Wallet'):
                $bet_transfer = $ledger->bet_transfer_resource;
                $currency_symbol = $bet_transfer->currency->currency_symbol;

                $html = (string)view('CRM.accounts.details.tabs.wallet.sources.wallet', [
                    'sender' => $bet_transfer->account->email,
                    'amount' => $currency_symbol . ' ' . number_format($bet_transfer->stake_amount, 8),
                    'created_at' => $ledger->created_at->toDayDateTimeString(),
                    'reason' => $bet_transfer->reason
                ]);
                break;
;
            case Source::getIdByName('Register'):
                $register_wallet = null;
                $currency_symbol = null;
                $currency_name = null;
                $amount = null;

                if (Registration::count()) {
                    $register_wallet = $ledger->register_wallet_resource();
                    $currency_symbol = $register_wallet->currency->currency_symbol;
                    $currency_name = $register_wallet->currency->currency_name;
                    $amount = $register_wallet->amount;
                } else {
                    $register_wallet = $ledger->wallet;
                    $currency_symbol = $register_wallet->currency->currency_symbol;
                    $currency_name = $register_wallet->currency->currency_name;
                    $amount = $ledger->debit;
                }
                $html = (string)view('CRM.accounts.details.tabs.wallet.sources.register_wallet', [
                    'register_wallet_name' => 'Registration ' . $currency_name,
                    'register_wallet_amount' => $currency_symbol . ' ' . number_format($amount, 8),
                    'register_wallet_type' => 'Registration',
                    'created_at' => $ledger->created_at->toDayDateTimeString()
                ]);
                break;

            case Source::getIdByName('GameResult'):
                $game_result = $ledger->game_result_resource;
                $currency_symbol = $game_result->currency->currency_symbol;

                $html = (string)view('CRM.accounts.details.tabs.wallet.sources.crm', [
                    'sender' => $game_result->game_name,
                    'amount' => $currency_symbol . ' ' . number_format($game_result->transfer_amount, 2),
                    'created_at' => $ledger->created_at->toDayDateTimeString(),
                    'reason' => '' . ucwords($game_result->result)
                ]);
                break;
        }
        return response()->json(compact('html'));
    }
}
