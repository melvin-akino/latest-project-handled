<?php

namespace App\Tasks;

use App\Models\Order;
use App\Jobs\WsMinMax;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class TransformKafkaMessageBet extends Task
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle()
    {
        $swoole = app('swoole');

        $topics = $swoole->topicTable;
        $ordersTable = $swoole->ordersTable;
        $wsTable = $swoole->wsTable;

        foreach ($topics AS $key => $row) {
            if (strpos($row['topic_name'], 'order-') === 0) {
                $orderId         = substr($row['topic_name'], strlen('order-'));
                $requestUIDArray = explode('-', $this->message->request_uid);
                $messageOrderId  = end($requestUIDArray);

                if ($orderId == $messageOrderId) {
                    Order::updateOrCreate([
                        'id' => $messageOrderId
                    ], [
                        'bet_id' => $this->message->data->bet_id,
                        'reason' => $this->message->data->reason,
                        'status' => 'SUCCESS'
                    ]);

                    $fd = $swoole->ws->get('uid:' . $row['user_id']);
                    $swoole->push($fd['value'], json_encode([
                        'getOrderStatus' => [
                            'order_id' => $orderId,
                            'status'   => 'SUCCESS'
                        ]
                    ]));

                    $topics->set('unique:' . uniqid(), [
                        'user_id'    => $row['user_id'],
                        'topic_name' => 'open-order-' . $this->message->data->bet_id
                    ]);

                    $ordersTable['orderId:' . $orderId]['bet_id'] = $this->message->data->bet_id;
                }
            }
        }
    }
}
