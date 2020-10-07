<?php

namespace App\Handlers;

use App\User;
use App\Models\{
    Order,
    OrderLogs,
    UserWallet,
    ExchangeRate,
    Source,
    OrderTransaction,
    ProviderAccountOrder,
    OddType
};

use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;

class SettlementTransformationHandler
{
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function init($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $swoole      = app('swoole');
        $orders      = $swoole->ordersTable;
        $providers   = $swoole->providersTable;
        $settlements = $this->data->data;

        foreach ($settlements AS $report) {
            $providerId       = $providers->get('providerAlias:' . $report->provider)['id'];
            $providerCurrency = $providers->get('providerAlias:' . $report->provider)['currency_id'];

            foreach ($orders AS $key => $row) {
                preg_match_all('!\d+!', $row['bet_id'], $mlBetIdArray);
                preg_match_all('!\d+!', $report->bet_id, $providerBetIdArray);

                $mlBetIdArrayIndex0 = $mlBetIdArray[0];
                $mlBetId = end($mlBetIdArrayIndex0);

                $providerBetIdArrayIndex0 = $providerBetIdArray[0];
                $providerBetId = end($providerBetIdArrayIndex0);

                if ($mlBetId == $providerBetId) {
                    if ($row['status'] == 'SUCCESS' || ($row['status'] == 'PENDING' && !empty($row['bet_id']))) {
                        $this->settledBets($report, $providerId, $providerCurrency);

                        $orders->del($key);
                    }
                }
            }
        }

        Log::info("Settlements - Processed");
    }

    private function settledBets($data, $providerId, $providerCurrency)
    {
        $status              = strtoupper($data->status);
        $balance             = 0;
        $stake               = 0;
        $sourceName          = "RETURN_STAKE";
        $stakeReturnToLedger = false;
        $transferAmount      = 0;
        $colMinusOne              = OddType::whereIn('type', ['1X2', 'HT 1X2', 'OE'])->pluck('id')->toArray();

        preg_match_all('!\d+!', $data->bet_id, $providerBetIdArray);
        $providerBetIdArrayIndex0 = $providerBetIdArray[0];
        $providerBetId = end($providerBetIdArrayIndex0);

        if ($status == "WON") {
            $status = "WIN";
        }

        if ($status == "LOSS") {
            $status = "LOSE";
        }

        $orders       = Order::where('bet_id', 'like', '%' . $providerBetId)->first();
        $userWallet   = UserWallet::where('user_id', $orders->user_id)->first();
        $userCurrency = User::where('id', $orders->user_id)->first();
        $exchangeRate = ExchangeRate::where('from_currency_id', $providerCurrency)
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
                $transferAmount      = $orders->to_win;

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
                $stakeReturnToLedger = true;
                $charge              = 'Credit';
                $transferAmount      = $orders->to_win / 2;

                break;
            case 'HALF LOSE':
                $balance        = ($orders->stake / 2) * -1;
                $debit          = 0;
                $credit         = $balance;
                $sourceName     = "BET_HALF_LOSE";
                $charge         = 'Credit';
                $transferAmount = $orders->stake / 2;

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

        $balance                  = $balance != 0 ? $balance * $exchangeRate->exchange_rate : 0;
        $sourceId                 = Source::where('source_name', 'LIKE', $sourceName)->first();
        $returnBetSourceId        = Source::where('source_name', 'LIKE', 'RETURN_STAKE')->first();

        DB::beginTransaction();

        try {
            Order::where('bet_id', 'like', '%' . $providerBetId)
                 ->update([
                     'status'        => strtoupper($data->status),
                     'profit_loss'   => $balance,
                     'reason'        => $data->reason,
                     'settled_date'  => Carbon::now(),
                     'updated_at'    => Carbon::now(),
                     'final_score'   => $data->score
                 ]);

            $orderLogs = OrderLogs::create([
                'provider_id'   => $providerId,
                'sport_id'      => $data->sport,
                'bet_id'        => $orders->bet_id,
                'bet_selection' => $orders->bet_selection,
                'status'        => $status,
                'user_id'       => $orders->user_id,
                'reason'        => $data->reason,
                'profit_loss'   => $balance,
                'order_id'      => $orders->id,
                'settled_date'  => Carbon::now(),
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ]);

            $orderLogsId    = $orderLogs->id;
            $chargeType     = $charge;
            $receiver       = $orders->user_id;
            $transferAmount = $transferAmount == 0 ? 0 : $transferAmount;
            $currency       = $userWallet->currency_id;
            $source         = $sourceId->id;
            $ledger         = UserWallet::makeTransaction($receiver, $transferAmount, $currency, $source, $chargeType);

            ProviderAccountOrder::create([
                'order_log_id'       => $orderLogsId,
                'exchange_rate_id'   => $exchangeRate->id,
                'actual_stake'       => $data->stake,
                'actual_to_win'      => !in_array($orders->odd_type_id, $colMinusOne) ? $data->stake * $data->odds : $data->stake * ($data->odds - 1),
                'actual_profit_loss' => $data->profit_loss,
                'exchange_rate'      => $exchangeRate->exchange_rate,
            ]);

            OrderTransaction::create([
                'order_logs_id'       => $orderLogsId,
                'user_id'             => $orders->user_id,
                'source_id'           => $sourceId->id,
                'currency_id'         => $userWallet->currency_id,
                'wallet_ledger_id'    => $ledger->id,
                'provider_account_id' => $orders->provider_account_id,
                'reason'              => $data->reason,
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
                    'reason'              => $data->reason,
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
                    'data'    => $data,
                ]
            ]));

            DB::rollBack();
        }
    }
}
