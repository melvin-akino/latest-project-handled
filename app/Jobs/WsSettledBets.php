<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\UserWallet;
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
        $status              = strtoupper($this->data->status);
        $balance             = 0;
        $stake               = 0;
        $sourceName          = "RETURN_STAKE";
        $stakereturntoledger = false;

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
                $stake               = $orders->stake;
                $balance             = $orders->to_win;
                $debit               = 0;
                $credit              = $balance;
                $sourceName          = "BET_WIN";
                $stakereturntoledger = true;
                $charge              = 'Credit';

                break;
            case 'LOSE':
                $balance    = $orders->stake * -1;
                $debit      = $balance;
                $credit     = 0;
                $sourceName = "BET_LOSE";
                $charge     = 'Debit';

                break;
            case 'HALF WIN':
                $stake               = $orders->stake;
                $balance             = $orders->to_win / 2;
                $debit               = 0;
                $credit              = $balance;
                $sourceName          = "BET_HALF_WIN";
                $stakereturntoledger = true;
                $charge              = 'Credit';

                break;
            case 'HALF LOSE':
                $balance    = $orders->stake / 2;
                $debit      = 0;
                $credit     = $balance;
                $sourceName = "BET_HALF_LOSE";
                $charge     = 'Debit';

                break;
            case 'PUSH':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;
                $charge  = 'Credit';

                break;
            case 'DRAW':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;
                $charge  = 'Credit';

                break;
            case 'CANCELLED':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;
                $charge  = 'Credit';

                break;
            case 'REJECTED':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;
                $charge  = 'Credit';

                break;
            case 'ABNORMAL BET':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;
                $charge  = 'Credit';

                break;
            case 'REFUNDED':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;
                $charge  = 'Credit';

                break;
        }

        $balance += $stake;

        $sourceId = DB::table('sources')
            ->where('source_name', 'LIKE', $sourceName)
            ->first();

        $returnBetSourceId = DB::table('sources')
            ->where('source_name', 'LIKE', 'RETURN_BET')
            ->first();

        try {
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

            $charge_type     = $charge;
            $receiver        = $orders->user_id;
            $transfer_amount = $stake * $exchangeRate->exchange_rate;
            $currency        = $userWallet->currency_id;
            $source          = $sourceId->id;
            $ledger          = UserWallet::makeTransaction($receiver, $transfer_amount, $currency, $source, $charge_type);

            DB::table('order_transactions')
                ->insert(
                    [
                        'order_logs_id'       => $orderLogsId,
                        'user_id'             => $orders->user_id,
                        'source_id'           => $sourceId->id,
                        'currency_id'         => $userWallet->currency_id,
                        'wallet_ledger_id'    => $ledger->id,
                        'provider_account_id' => $orders->provider_account_id,
                        'reason'              => $this->data->reason,
                        'amount'              => $balance,
                        'created_at'          => Carbon::now(),
                        'updated_at'          => Carbon::now(),
                    ]
                );

            if ($stakereturntoledger == true) {
                $transfer_amount = $stake;
                $receiver        = $orders->user_id;
                $currency        = $userWallet->currency_id;
                $source          = $returnBetSourceId->id;
                $charge_type     = "Credit";
                $ledger          = UserWallet::makeTransaction($receiver, $transfer_amount, $currency, $source, $charge_type);

                DB::table('order_transactions')
                    ->insert(
                        [
                            'order_logs_id'       => $orderLogsId,
                            'user_id'             => $orders->user_id,
                            'source_id'           => $returnBetSourceId->id,
                            'currency_id'         => $userWallet->currency_id,
                            'wallet_ledger_id'    => $ledger->id,
                            'provider_account_id' => $orders->provider_account_id,
                            'reason'              => $this->data->reason,
                            'amount'              => $stake,
                            'created_at'          => Carbon::now(),
                            'updated_at'          => Carbon::now(),
                        ]
                    );
            }

            DB::commit();
        } catch (\Exception $e) {
            \Log::error(json_encode(
                [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file'    => $e->getFile(),
                ]
            ));

            DB::rollBack();
        }
    }
}
