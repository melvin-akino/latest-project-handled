<?php

namespace App\Jobs;

use App\Models\{
    Order,
    ExchangeRate,
    UserWallet,
    Source,
    OrderLogs,
    OrderTransaction,
    ProviderAccountOrder
};
use App\Models\CRM\{
    ProviderAccount,
    WalletLedger
};
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\{DB, Log};

class TransformKafkaMessageOpenOrders implements ShouldQueue
{
    use Dispatchable;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            $swoole      = app('swoole');
            $ordersTable = $swoole->ordersTable;
            $providers   = $swoole->providersTable;
            $openOrders  = $this->data->data;

            foreach ($ordersTable as $_key => $orderTable) {
                $orderId          = substr($_key, strlen('orderId:'));
                $expiry           = $orderTable['orderExpiry'];
                $status           = $orderTable['status'];
                $orderData        = Order::find($orderId);
                $userWallet       = UserWallet::where('user_id', $orderData->user_id)->first();
                $sourceId         = Source::where('source_name', 'LIKE', 'PLACE_BET')->first();
                $userId           = $orderData->user_id;
                $orderLogsId      = 0;
                $walletLedgerId   = 0;
                $credit           = 0;
                $reason           = "";

                if (!empty($openOrders)) {
                    foreach ($openOrders as $order) {
                        $betId = $order->bet_id;
                        $providerCurrency = $providers->get('providerAlias:' . $order->provider)['currency_id'];
                        $exchangeRate     = ExchangeRate::where('from_currency_id', $providerCurrency)->where('to_currency_id', 1)->first();
                        $stake            = $order->stake * $exchangeRate->exchange_rate;

                        if (time() - strtotime($orderTable['created_at']) > $expiry && $status == 'PENDING' && empty($orderData->bet_id)) {
                            $walletLedger = $this->bookMakerCantBeReached($orderTable, $orderId, $orderData, $userId, $userWallet, $exchangeRate, $sourceId);
                            $walletLedgerId = $walletLedger->id;

                            WSOrderStatus::dispatch($userId, $orderId, 'FAILED', $orderData->odds, $expiry, $orderTable['created_at']);

                            $ordersTable->del($_key);
                        } else {
                            if ($orderTable['bet_id'] == $betId) {
                                if (strtoupper($order->status) != strtoupper($orderData->status)) {
                                    $ordersTable[$_key]['status'] = strtoupper($order->status);
                                    $reason                       = $order->reason;
                                    $betSelectionArray            = explode("\n", $orderData->bet_selection);
                                    $betSelectionTeamOddsArray    = explode('@ ', $betSelectionArray[1]);
                                    $updatedOrderOdds             = $betSelectionTeamOddsArray[0] . '@ ' . number_format($order->odds, 2);
                                    $betSelection                 = implode("\n", [ $betSelectionArray[0], $updatedOrderOdds, $betSelectionArray[2] ]);

                                    Order::where('id', $orderId)->update([
                                        'provider_account_id' => ProviderAccount::getUsernameId($orderTable['username']),
                                        'status'              => strtoupper($order->status),
                                        'odds'                => $order->odds,
                                        'reason'              => $reason,
                                        'bet_selection'       => $betSelection,
                                        'to_win'              => $orderData->stake * $order->odds,
                                        'bet_id'              => $betId,
                                    ]);

                                    $orderLogs = OrderLogs::create([
                                        'provider_id'   => $orderData->provider_id,
                                        'sport_id'      => $orderData->sport_id,
                                        'bet_id'        => $betId,
                                        'bet_selection' => $betSelection,
                                        'status'        => strtoupper($order->status),
                                        'user_id'       => $userId,
                                        'reason'        => $reason,
                                        'profit_loss'   => 0.00,
                                        'order_id'      => $orderId,
                                        'settled_date'  => $orderData->settled_date
                                    ]);

                                    $orderLogsId = $orderLogs->id;

                                    ProviderAccountOrder::create([
                                        'order_log_id'       => $orderLogsId,
                                        'exchange_rate_id'   => $exchangeRate->id,
                                        'actual_stake'       => $stake,
                                        'actual_to_win'      => $stake * $order->odds,
                                        'actual_profit_loss' => 0.00,
                                        'exchange_rate'      => $exchangeRate->exchange_rate,
                                    ]);

                                    if (in_array(strtoupper($order->status), [
                                        'FAILED',
                                        'CANCELLED',
                                    ])) {
                                        $credit     = $orderData->stake;
                                        $balance    = $credit;
                                        $newBalance = $userWallet->balance + $balance;

                                        UserWallet::where('user_id', $orderData->user_id)
                                                  ->update([
                                                      'balance'    => $newBalance,
                                                      'updated_at' => Carbon::now(),
                                                  ]);

                                        $walletLedger = WalletLedger::create([
                                            'wallet_id'  => $userWallet->id,
                                            'source_id'  => $sourceId->id,
                                            'debit'      => 0,
                                            'credit'     => $credit,
                                            'balance'    => $newBalance
                                        ]);

                                        $walletLedgerId = $walletLedger->id;
                                    }

                                    WSOrderStatus::dispatch($userId, $orderId, strtoupper($order->status), $order->odds, $expiry, $orderTable['created_at']);

                                    if (in_array(strtoupper($order->status), [
                                        'FAILED',
                                        'CANCELLED',
                                    ])) {
                                        $ordersTable->del($_key);
                                    }
                                }
                            }
                        }

                        OrderTransaction::create([
                            'order_logs_id'       => $orderLogsId,
                            'user_id'             => $userId,
                            'source_id'           => $sourceId->id,
                            'currency_id'         => $providerCurrency,
                            'wallet_ledger_id'    => $walletLedgerId,
                            'provider_account_id' => ProviderAccount::getUsernameId($orderTable['username']),
                            'reason'              => $reason,
                            'amount'              => $credit
                        ]);
                    }
                } else if (time() - strtotime($orderTable['created_at']) > $expiry && $status == 'PENDING' && empty($orderData->bet_id)) {
                    $orderAliasStake = Order::getOrderProviderAlias($orderId);
                    $providerCurrency = $providers->get('providerAlias:' . strtolower($orderAliasStake->alias))['currency_id'];
                    $exchangeRate     = ExchangeRate::where('from_currency_id', $providerCurrency)->where('to_currency_id', 1)->first();

                    $walletLedger = $this->bookMakerCantBeReached($orderTable, $orderId, $orderData, $userId, $userWallet, $exchangeRate, $sourceId);
                    $walletLedgerId = $walletLedger->id;

                    WSOrderStatus::dispatch($userId, $orderId, 'FAILED', $orderData->odds, $expiry, $orderTable['created_at']);

                    $ordersTable->del($_key);

                    OrderTransaction::create([
                        'order_logs_id'       => $orderLogsId,
                        'user_id'             => $userId,
                        'source_id'           => $sourceId->id,
                        'currency_id'         => $providerCurrency,
                        'wallet_ledger_id'    => $walletLedgerId,
                        'provider_account_id' => ProviderAccount::getUsernameId($orderTable['username']),
                        'reason'              => $reason,
                        'amount'              => $credit
                    ]);
                }
            }

            Log::info("Open Orders - Processed");

            DB::commit();
        } catch (Exception $e) {
            Log::error(json_encode([
                'TransformKafkaMessageOpenOrders' => [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                ]
            ]));

            DB::rollBack();
        }
    }

    private function bookMakerCantBeReached($orderTable, $orderId, $orderData, $userId, $userWallet, $exchangeRate, $sourceId)
    {
        $reason = "Bookmaker can't be reached";

        Order::where('id', $orderId)->update([
            'provider_account_id' => ProviderAccount::getUsernameId($orderTable['username']),
            'status'              => 'FAILED',
            'reason'              => $reason,
            'updated_at'          => Carbon::now(),
        ]);

        $lastOrderLogs = OrderLogs::where('order_id', $orderId)->orderBy('id', 'desc')->limit(1)->first();
        $providerAccountOrder = ProviderAccountOrder::where('order_log_id', $lastOrderLogs->id)->first();

        $orderLogs = OrderLogs::create([
            'provider_id'   => $orderData->provider_id,
            'sport_id'      => $orderData->sport_id,
            'bet_id'        => $orderData->bet_id,
            'bet_selection' => $orderData->bet_selection,
            'status'        => 'FAILED',
            'user_id'       => $userId,
            'reason'        => $reason,
            'profit_loss'   => 0.00,
            'order_id'      => $orderId,
            'settled_date'  => $orderData->settled_date
        ]);

        $orderLogsId = $orderLogs->id;
        $credit      = $orderData->stake;
        $balance     = $credit;
        $newBalance  = $userWallet->balance + $balance;

        ProviderAccountOrder::create([
            'order_log_id'       => $orderLogsId,
            'exchange_rate_id'   => $exchangeRate->id,
            'actual_stake'       => $providerAccountOrder->actual_stake,
            'actual_to_win'      => $providerAccountOrder->actual_to_win,
            'actual_profit_loss' => 0.00,
            'exchange_rate'      => $providerAccountOrder->exchange_rate,
        ]);

        UserWallet::where('user_id', $orderData->user_id)
                  ->update([
                      'balance'    => $newBalance,
                      'updated_at' => Carbon::now(),
                  ]);

        $walletLedger = WalletLedger::create([
            'wallet_id'  => $userWallet->id,
            'source_id'  => $sourceId->id,
            'debit'      => 0,
            'credit'     => $credit,
            'balance'    => $newBalance
        ]);

        return $walletLedger;
    }
}
