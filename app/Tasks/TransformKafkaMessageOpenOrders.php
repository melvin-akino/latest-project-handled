<?php

namespace App\Tasks;

use App\Models\Order;
use App\Models\CRM\ProviderAccount;
use App\Jobs\WSOrderStatus;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class TransformKafkaMessageOpenOrders extends Task
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        $swoole      = app('swoole');
        $topics      = $swoole->topicTable;
        $ordersTable = $swoole->ordersTable;
        $openOrders  = $this->data->data;

        foreach ($openOrders as $order) {
            foreach ($topics as $key => $topic) {
                if (strpos($topic['topic_name'], 'open-order-') === 0) {
                    $betId  = substr($topic['topic_name'], strlen('open-order-'));
                    $userId = $topic['user_id'];

                    foreach ($ordersTable as $_key => $orderTable) {
                        $orderId = substr($_key, strlen('orderId:'));
                        $expiry  = $orderTable['orderExpiry'];
                        $status  = $orderTable['status'];
                        if (time() - strtotime($orderTable['created_at']) > $expiry &&
                            $status == 'PENDING'
                        ) {
                            Order::where('id', $orderId)->update([
                                'provider_account_id' => ProviderAccount::getUsernameId($orderTable['username']),
                                'status'              => 'FAILED',
                                'odds'                => $order->odds,
                            ]);

                            WSOrderStatus::dispatch($userId, $orderId, 'FAILED', $order->odds, $expiry, $orderTable['created_at']);
                        } else if ($orderTable['bet_id'] == $betId) {
                            Order::where('id', $orderId)->update([
                                'provider_account_id' => ProviderAccount::getUsernameId($orderTable['username']),
                                'status'              => strtoupper($order->status),
                                'odds'                => $order->odds,
                            ]);

                            WSOrderStatus::dispatch($userId, $orderId, strtoupper($order->status), $order->odds, $expiry, $orderTable['created_at']);
                        }
                    }
                }
            }
        }
    }
}
