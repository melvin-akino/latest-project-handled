<?php

namespace App\Jobs;

use App\User;
use App\Models\{
    Order,
    OrderLogs,
    UserWallet,
    ExchangeRate,
    Source,
    OrderTransaction,
    ProviderAccountOrder
};

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\{DB, Log};
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
        $status              = strtoupper($this->data->status);
        $balance             = 0;
        $stake               = 0;
        $sourceName          = "RETURN_STAKE";
        $stakeReturnToLedger = false;
        $transferAmount      = 0;

        if ($status == "WON") {
            $status = "WIN";
        }

        if ($status == "LOSS") {
            $status = "LOSE";
        }

        $orders       = Order::where('bet_id', $this->data->bet_id)->first();
        $userWallet   = UserWallet::where('user_id', $orders->user_id)->first();
        $userCurrency = User::where('id', $orders->user_id)->first();
        $exchangeRate = ExchangeRate::where('from_currency_id', $this->providerCurrency)
                                    ->where('to_currency_id', $userCurrency->currency_id)
                                    ->first();

        switch ($status) {
            case 'WIN':
                $stake               = $orders->stake;
                $balance             = $orders->to_win;
                $debit               = 0;
                $credit              = $balance;
                $sourceName          = "BET_WIN";
                $stakeReturnToLedger = true;
                $charge              = 'Credit';
                $transferAmount      = $orders->to_win - $orders->stake;

                break;
            case 'LOSE':
                $balance        = $orders->stake * -1;
                $debit          = $balance;
                $credit         = 0;
                $sourceName     = "BET_LOSE";
                $charge         = 'Debit';
                $transferAmount = 0;

                break;
            case 'HALF WIN':
                $stake               = $orders->stake;
                $balance             = $orders->to_win / 2;
                $debit               = 0;
                $credit              = $balance;
                $sourceName          = "BET_HALF_WIN";
                $stakeReturnToLedger = true;
                $charge              = 'Credit';
                $transferAmount      = ($orders->to_win - $orders->stake) / 2;

                break;
            case 'HALF LOSE':
                $balance        = $orders->stake / 2;
                $debit          = 0;
                $credit         = $balance;
                $sourceName     = "BET_HALF_LOSE";
                $charge         = 'Debit';
                $transferAmount = ($orders->to_win - $orders->stake) / 2;

                break;
            case 'PUSH':
            case 'VOID':
            case 'DRAW':
            case 'CANCELLED':
            case 'REJECTED':
            case 'ABNORMAL BET':
            case 'REFUNDED':
                $balance        = 0;
                $debit          = 0;
                $credit         = $balance;
                $charge         = 'Credit';
                $transferAmount = $orders->stake;
                break;
        }

        $balance                  = !empty($balance) ? $balance * $exchangeRate->exchange_rate : 0;
        $sourceId                 = Source::where('source_name', 'LIKE', $sourceName)->first();
        $returnBetSourceId        = Source::where('source_name', 'LIKE', 'RETURN_STAKE')->first();
        $score                    = $this->data->score;
        $betSelectionArray        = explode("\n", $orders->bet_selection);
        $betSelectionOddsAndScore = explode("(", $betSelectionArray[2]);
        $updatedOddsAndScore      = $betSelectionOddsAndScore[0] . " (" . $score . ")";
        $updatedBetSelection      = implode("\n", [
            $betSelectionArray[0],
            $betSelectionArray[1],
            $updatedOddsAndScore
        ]);

        DB::beginTransaction();

        try {
            Order::where('bet_id', $this->data->bet_id)
                 ->update([
                     'bet_selection' => $updatedBetSelection,
                     'status'        => strtoupper($this->data->status),
                     'profit_loss'   => $balance,
                     'reason'        => $this->data->reason,
                     'settled_date'  => Carbon::now(),
                     'updated_at'    => Carbon::now(),
                 ]);

            $orderLogs = OrderLogs::create([
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

            $orderLogsId    = $orderLogs->id;
            $chargeType     = $charge;
            $receiver       = $orders->user_id;
            $transferAmount = $transferAmount ?: ($orders->to_win - $stake);
            $currency       = $userWallet->currency_id;
            $source         = $sourceId->id;
            $ledger         = UserWallet::makeTransaction($receiver, $transferAmount, $currency, $source, $chargeType);

            ProviderAccountOrder::create([
                'order_log_id'       => $orderLogsId,
                'exchange_rate_id'   => $exchangeRate->id,
                'actual_stake'       => $this->data->stake,
                'actual_to_win'      => $this->data->stake * $this->data->odds,
                'actual_profit_loss' => $this->data->profit_loss,
                'exchange_rate'      => $exchangeRate->exchange_rate,
            ]);

            OrderTransaction::create([
                'order_logs_id'       => $orderLogsId,
                'user_id'             => $orders->user_id,
                'source_id'           => $sourceId->id,
                'currency_id'         => $userWallet->currency_id,
                'wallet_ledger_id'    => $ledger->id,
                'provider_account_id' => $orders->provider_account_id,
                'reason'              => $this->data->reason,
                'amount'              => $balance
            ]);

            if ($stakeReturnToLedger == true) {
                $transferAmount = $stake;
                $receiver       = $orders->user_id;
                $currency       = $userWallet->currency_id;
                $source         = $returnBetSourceId->id;
                $chargeType     = "Credit";
                $ledger         = UserWallet::makeTransaction($receiver, $transferAmount, $currency, $source, $chargeType);

                OrderTransaction::create([
                    'order_logs_id'       => $orderLogsId,
                    'user_id'             => $orders->user_id,
                    'source_id'           => $returnBetSourceId->id,
                    'currency_id'         => $userWallet->currency_id,
                    'wallet_ledger_id'    => $ledger->id,
                    'provider_account_id' => $orders->provider_account_id,
                    'reason'              => $this->data->reason,
                    'amount'              => $stake
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            Log::error(json_encode([
                'WS_SETTLED_BETS' => [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file'    => $e->getFile(),
                    'data'    => $this->data,
                ]
            ]));

            DB::rollBack();
        }
    }
}
