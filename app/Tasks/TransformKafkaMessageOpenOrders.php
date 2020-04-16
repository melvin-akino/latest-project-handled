<?php

namespace App\Tasks;

use App\Jobs\{
    WSForBetBarRemoval,
    DbOrderStatus
};
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
        $ws          = $swoole->wsTable;
        $ordersTable = $swoole->ordersTable;
        $openOrders  = $this->data->data;

        foreach ($openOrders as $order) {
            foreach ($topics as $key => $topic) {
                if (strpos($topic['topic_name'], 'open-order-') === 0) {
                    $betId  = substr($topic['topic_name'], strlen('open-order-'));
                    $userId = $topic['user_id'];

                    foreach ($ordersTable as $_key => $orderTable) {
                        if ($orderTable['bet_id'] == $betId) {

                            $expiry  = $orderTable['orderExpiry'];
                            $orderId = substr($_key, strlen('orderId:'));

                            DbOrderStatus::dispatch($userId, $orderId, $order->status);
                        }
                    }
                }
            }
        }
    }
}
