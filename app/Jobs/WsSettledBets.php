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
        $status  = strtoupper($this->data->status);
        $balance = 0;

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

        $sourceId = DB::table('sources')
            ->where('source_name', 'LIKE', 'PLACE_BET')
            ->first();

        $exchangeRate = DB::table('exchange_rates')
            ->where('from_currency_id', $this->providerCurrency)
            ->where('to_currency_id', 1)
            ->first();

        switch ($status) {
            case 'WIN':
                $balance = $orders->to_win;
                $debit   = 0;
                $credit  = $balance;

                break;
            case 'LOSE':
                $balance = $orders->to_win * -1;
                $debit   = $balance;
                $credit  = 0;

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
            case 'HALF WIN':
                $balance = $orders->to_win / 2;
                $debit   = 0;
                $credit  = $balance;

                break;
            case 'HALF LOSE':
                $balance = ($orders->to_win / 2) - 1;
                $debit   = $balance;
                $credit  = 0;

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
        }

        DB::table('orders')->where('bet_id', $this->data->bet_id)
            ->update([
            'status'       => strtoupper($this->data->status),
            'profit_loss'  => $balance,
            'reason'       => $this->data->reason,
            'settled_date' => Carbon::now(),
            'updated_at'   => Carbon::now(),
        ]);

        DB::table('order_logs')
            ->insert([
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
            ]);

        $balance   *= $exchangeRate->exchange_rate;
        $newBalance = $userWallet->balance + $balance;

        DB::table('wallet')->where('user_id', $orders->user_id)
            ->update([
            'balance'    => $newBalance,
            'updated_at' => Carbon::now(),
        ]);

        DB::table('wallet_ledger')
            ->insert([
                'wallet_id'  => $userWallet->id,
                'source_id'  => $sourceId->id,
                'debit'      => $debit,
                'credit'     => $credit,
                'balance'    => $newBalance,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
    }
}
