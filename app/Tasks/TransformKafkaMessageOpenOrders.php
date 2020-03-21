<?php

namespace App\Tasks;

use App\Jobs\WsMinMax;
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
        $swoole         = app('swoole');
        $topics         = $swoole->topicTable;
        $ordersTable    = $swoole->ordersTable;
        $openOrders     = $this->data->data;
        foreach ($openOrders as $order) {
            foreach ($topics as $key => $topic) {
                if (strpos($topic['topic_name'], 'open-order-') === 0) {
                    $betId  = substr($topic['topic_name'], strlen('open-order-'));
                    $userId = $topic['user_id'];
                    foreach ($ordersTable as $_key => $orderTable) {
                        if ($orderTable['bet_id'] == $betId)) {
                            $fd = $wsTable->get('uid:' . $userId);
                            $swoole->push($fd['value'], 
                                json_encode([
                                    'getOrderStatus' => [
                                        'order_id'   => substr($_key, strlen('orderId:')),
                                        'status'     => $order->status
                                    ]
                                ])
                            );
                        }
                    }
                }
            }
        }
    }
}