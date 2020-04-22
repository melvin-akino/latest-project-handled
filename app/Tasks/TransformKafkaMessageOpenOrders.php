<?php

namespace App\Tasks;

use App\Models\Order;
use App\Models\CRM\ProviderAccount;
use App\Jobs\WSOrderStatus;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\{DB, Log};
use Exception;

class TransformKafkaMessageOpenOrders extends Task
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        try {
            DB::beginTransaction();

            $swoole      = app('swoole');
            $topics      = $swoole->topicTable;
            $ordersTable = $swoole->ordersTable;
            $providers   = $swoole->providersTable;
            $openOrders  = $this->data->data;

            foreach ($openOrders as $order) {
                $betId  = $order->bet_id;
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

                    Log::info('Open ORDER - ' . (time() - strtotime($orderTable['created_at']) > $expiry));
                    Log::info('Open ORDER - ' . $status);
                    if (time() - strtotime($orderTable['created_at']) > $expiry &&
                        $status == 'PENDING'
                    ) {
                        Order::where('id', $orderId)->update([
                            'provider_account_id' => ProviderAccount::getUsernameId($orderTable['username']),
                            'status'              => 'FAILED',
                            'odds'                => $order->odds,
                            'reason'              => 'No Kafka payload received',
                            'updated_at'          => Carbon::now(),
                        ]);
                        
                        DB::table('order_logs')
                            ->insert([
                                'provider_id'   => $orderData->provider_id,
                                'sport_id'      => $orderData->sport_id,
                                'bet_id'        => $orderData->bet_id,
                                'bet_selection' => $orderData->bet_selection,
                                'status'        => 'FAILED',
                                'user_id'       => $userId,
                                'reason'        => 'No Kafka payload received',
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

                        DB::table('wallet_ledger')
                            ->insert([
                                'wallet_id'  => $userWallet->id,
                                'source_id'  => $sourceId->id,
                                'debit'      => 0,
                                'credit'     => $credit,
                                'balance'    => $newBalance,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ]);

                        WSOrderStatus::dispatch($userId, $orderId, 'FAILED', $order->odds, $expiry,
                            $orderTable['created_at']);
                    } else {
                        if ($orderTable['bet_id'] == $betId) {
                            Order::where('id', $orderId)->update([
                                'provider_account_id' => ProviderAccount::getUsernameId($orderTable['username']),
                                'status'              => strtoupper($order->status),
                                'odds'                => $order->odds,
                            ]);

                            WSOrderStatus::dispatch($userId, $orderId, strtoupper($order->status), $order->odds,
                                $expiry, $orderTable['created_at']);
                        }
                    }
                }
            }
            DB::commit();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
        }
    }
}
