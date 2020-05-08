<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\CRM\ProviderAccount;
use App\Jobs\WSOrderStatus;

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

            foreach ($openOrders as $order) {
                $betId = $order->bet_id;

                foreach ($ordersTable as $_key => $orderTable) {
                    $orderId = substr($_key, strlen('orderId:'));
                    $expiry  = $orderTable['orderExpiry'];
                    $status  = $orderTable['status'];

                    $providerCurrency = $providers->get('providerAlias:' . $order->provider)['currency_id'];

                    $exchangeRate = DB::table('exchange_rates')
                        ->where('from_currency_id', $providerCurrency)
                        ->where('to_currency_id', 1)
                        ->first();

                    $orderData = DB::table('orders')
                        ->where('id', $orderId)
                        ->first();

                    $userWallet = DB::table('wallet')
                        ->where('user_id', $orderData->user_id)
                        ->first();

                    $sourceId = DB::table('sources')
                        ->where('source_name', 'LIKE', 'PLACE_BET')
                        ->first();

                    $userId = $orderData->user_id;

                    $orderLogsId    = 0;
                    $walletLedgerId = 0;
                    $credit         = 0;
                    $reason         = "";

                    if (time() - strtotime($orderTable['created_at']) > $expiry &&
                        $status == 'PENDING' && empty($orderData->bet_id)
                    ) {
                        $reason = "No Kafka payload received";

                        Order::where('id', $orderId)->update([
                            'provider_account_id' => ProviderAccount::getUsernameId($orderTable['username']),
                            'status'              => 'FAILED',
                            'reason'              => $reason,
                            'updated_at'          => Carbon::now(),
                        ]);

                        $orderLogsId = DB::table('order_logs')
                            ->insertGetId([
                                'provider_id'   => $orderData->provider_id,
                                'sport_id'      => $orderData->sport_id,
                                'bet_id'        => $orderData->bet_id,
                                'bet_selection' => $orderData->bet_selection,
                                'status'        => 'FAILED',
                                'user_id'       => $userId,
                                'reason'        => $reason,
                                'profit_loss'   => $orderData->profit_loss,
                                'order_id'      => $orderId,
                                'settled_date'  => $orderData->settled_date,
                                'created_at'    => Carbon::now(),
                                'updated_at'    => Carbon::now(),
                            ]);

                        $credit     = $orderData->stake;
                        $balance    = $credit * $exchangeRate->exchange_rate;
                        $newBalance = $userWallet->balance + $balance;

                        DB::table('wallet')->where('user_id', $orderData->user_id)
                            ->update([
                                'balance'    => $newBalance,
                                'updated_at' => Carbon::now(),
                            ]);

                        $walletLedgerId = DB::table('wallet_ledger')
                            ->insertGetId([
                                'wallet_id'  => $userWallet->id,
                                'source_id'  => $sourceId->id,
                                'debit'      => 0,
                                'credit'     => $credit,
                                'balance'    => $newBalance,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ]);

                        WSOrderStatus::dispatch($userId, $orderId, 'FAILED', $orderData->odds, $expiry, $orderTable['created_at']);

                        $ordersTable->del($_key);
                    } else {
                        if ($orderTable['bet_id'] == $betId) {
                            if (strtoupper($order->status) != strtoupper($orderData->status)) {
                                $ordersTable[$_key]['status'] = strtoupper($order->status);

                                $reason = $order->reason;

                                $betSelectionArray         = explode("\n", $orderData->bet_selection);
                                $betSelectionTeamOddsArray = explode('@ ', $betSelectionArray[1]);
                                $updatedOrderOdds          = $betSelectionTeamOddsArray[0] . '@ ' . number_format($order->odds, 2);
                                $betSelection              = implode("\n", [ $betSelectionArray[0], $updatedOrderOdds, $betSelectionArray[2] ]);

                                Order::where('id', $orderId)->update([
                                    'provider_account_id' => ProviderAccount::getUsernameId($orderTable['username']),
                                    'status'              => strtoupper($order->status),
                                    'odds'                => $order->odds,
                                    'actual_to_win'       => $orderData->actual_stake * $order->odds,
                                    'reason'              => $reason,
                                    'bet_selection'       => $betSelection,
                                    'to_win'              => $orderData->stake * $order->odds,
                                ]);

                                $orderLogsId = DB::table('order_logs')
                                    ->insertGetId([
                                        'provider_id'   => $orderData->provider_id,
                                        'sport_id'      => $orderData->sport_id,
                                        'bet_id'        => $orderData->bet_id,
                                        'bet_selection' => $betSelection,
                                        'status'        => strtoupper($order->status),
                                        'user_id'       => $userId,
                                        'reason'        => $reason,
                                        'profit_loss'   => $orderData->profit_loss,
                                        'order_id'      => $orderId,
                                        'settled_date'  => $orderData->settled_date,
                                        'created_at'    => Carbon::now(),
                                        'updated_at'    => Carbon::now(),
                                    ]);

                                if (in_array(strtoupper($order->status), [
                                    'FAILED',
                                    'CANCELLED',
                                ])) {
                                    $credit     = $orderData->stake;
                                    $balance    = $credit * $exchangeRate->exchange_rate;
                                    $newBalance = $userWallet->balance + $balance;

                                    DB::table('wallet')->where('user_id', $orderData->user_id)
                                        ->update([
                                            'balance'    => $newBalance,
                                            'updated_at' => Carbon::now(),
                                        ]);

                                    $walletLedgerId = DB::table('wallet_ledger')
                                        ->insertGetId([
                                            'wallet_id'  => $userWallet->id,
                                            'source_id'  => $sourceId->id,
                                            'debit'      => 0,
                                            'credit'     => $credit,
                                            'balance'    => $newBalance,
                                            'created_at' => Carbon::now(),
                                            'updated_at' => Carbon::now(),
                                        ]);
                                }

                                WSOrderStatus::dispatch($userId, $orderId, strtoupper($order->status), $order->odds,
                                    $expiry, $orderTable['created_at']);

                                if (in_array(strtoupper($order->status), [
                                    'FAILED',
                                    'CANCELLED',
                                ])) {
                                    $ordersTable->del($_key);
                                }
                            }
                        }
                    }

                    DB::table('order_transactions')
                        ->insert(
                            [
                                'order_logs_id'       => $orderLogsId,
                                'user_id'             => $userId,
                                'source_id'           => $sourceId->id,
                                'currency_id'         => $providerCurrency,
                                'wallet_ledger_id'    => $walletLedgerId,
                                'provider_account_id' => ProviderAccount::getUsernameId($orderTable['username']),
                                'reason'              => $reason,
                                'amount'              => $credit,
                                'created_at'          => Carbon::now(),
                                'updated_at'          => Carbon::now(),
                            ]
                        );
                }
            }
            DB::commit();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
        }
    }
}