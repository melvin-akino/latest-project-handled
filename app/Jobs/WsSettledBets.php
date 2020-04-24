<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WsSettledBets implements ShouldQueue
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $providerId, $providerCurrency)
    {
        $this->data             = $data;
        $this->providerId       = $providerId;
        $this->providerCurrency = $providerCurrency;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status     = strtoupper($this->data->status);
        $balance    = 0;
        $stake      = 0;
        $sourceName = "RETURN_BET";

        if ($status == "WON") {
            $status = "WIN";
        }

        if ($status == "LOSS") {
            $status = "LOSE";
        }

        $orders = DB::table('orders')
            ->where('bet_id', $this->data->bet_id)
            ->first();

        $userWallet = DB::table('wallet')
            ->where('user_id', $orders->user_id)
            ->first();

        $exchangeRate = DB::table('exchange_rates')
            ->where('from_currency_id', $this->providerCurrency)
            ->where('to_currency_id', 1)
            ->first();

        switch ($status) {
            case 'WIN':
                $stake      = $orders->stake;
                $balance    = $orders->to_win;
                $debit      = 0;
                $credit     = $balance;
                $sourceName = "BET_WINNING";

                break;
            case 'LOSE':
                $balance    = $orders->stake * -1;
                $debit      = $balance;
                $credit     = 0;
                $sourceName = "BET_LOSS";

                break;
            case 'HALF WIN':
                $stake      = $orders->stake;
                $balance    = $orders->to_win / 2;
                $debit      = 0;
                $credit     = $balance;
                $sourceName = "BET_WINNING";

                break;
            case 'HALF LOSE':
                $balance    = $orders->stake / 2;
                $debit      = $balance;
                $credit     = 0;
                $sourceName = "BET_LOSS";

                break;
            case 'PUSH':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;

                break;
            case 'DRAW':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;

                break;
            case 'CANCELLED':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;

                break;
            case 'REJECTED':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;

                break;
            case 'ABNORMAL BET':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;

                break;
            case 'REFUNDED':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;

                break;
        }

        $balance += $stake;

        $sourceId = DB::table('sources')
            ->where('source_name', 'LIKE', $sourceName)
            ->first();

        $returnBetSourceId = DB::table('sources')
            ->where('source_name', 'LIKE', 'RETURN_BET')
            ->first();

        DB::table('orders')->where('bet_id', $this->data->bet_id)
            ->update(
                [
                    'status'       => strtoupper($this->data->status),
                    'profit_loss'  => $balance,
                    'reason'       => $this->data->reason,
                    'settled_date' => Carbon::now(),
                    'updated_at'   => Carbon::now(),
                ]
            );

        $orderLogsId = DB::table('order_logs')
            ->insertGetId(
                [
                    'provider_id'   => $this->providerId,
                    'sport_id'      => $this->data->sport,
                    'bet_id'        => $this->data->bet_id,
                    'bet_selection' => $orders->bet_selection,
                    'status'        => $status,
                    'user_id'       => $orders->user_id,
                    'reason'        => $this->data->reason,
                    'profit_loss'   => $balance,
                    'order_id'      => $orders->id,
                    'settled_date'  => Carbon::now(),
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now(),
                ]
            );

        $balance   *= $exchangeRate->exchange_rate;
        $newBalance = $userWallet->balance + $balance;

        DB::table('wallet')->where('user_id', $orders->user_id)
            ->update(
                [
                    'balance'    => $newBalance,
                    'updated_at' => Carbon::now(),
                ]
            );

        $walletLedgerId = DB::table('wallet_ledger')
            ->insertGetId(
                [
                    'wallet_id'  => $userWallet->id,
                    'source_id'  => $sourceId->id,
                    'debit'      => $debit,
                    'credit'     => $credit,
                    'balance'    => $newBalance,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );

        DB::table('order_transactions')
            ->insert(
                [
                    'order_logs_id'       => $orderLogsId,
                    'user_id'             => $orders->user_id,
                    'source_id'           => $sourceId->id,
                    'currency_id'         => $this->providerCurrency,
                    'wallet_ledger_id'    => $walletLedgerId,
                    'provider_account_id' => $orders->provider_account_id,
                    'reason'              => $this->data->reason,
                    'amount'              => $balance,
                    'created_at'          => Carbon::now(),
                    'updated_at'          => Carbon::now(),
                ]
            );

        if ($stake != 0) {
            $returnLedgerId = DB::table('wallet_ledger')
                ->insertGetId(
                    [
                        'wallet_id'  => $userWallet->id,
                        'source_id'  => $returnBetSourceId->id,
                        'debit'      => 0,
                        'credit'     => $stake,
                        'balance'    => $newBalance,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]
                );

            DB::table('order_transactions')
                ->insert(
                    [
                        'order_logs_id'       => $orderLogsId,
                        'user_id'             => $orders->user_id,
                        'source_id'           => $returnBetSourceId->id,
                        'currency_id'         => $this->providerCurrency,
                        'wallet_ledger_id'    => $returnLedgerId,
                        'provider_account_id' => $orders->provider_account_id,
                        'reason'              => 'Returned Stake',
                        'amount'              => $stake,
                        'created_at'          => Carbon::now(),
                        'updated_at'          => Carbon::now(),
                    ]
                );
        }
    }
}
