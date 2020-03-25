<?php

namespace App\Tasks;

use App\Jobs\{
    WSForBetBarRemoval,
    WsMinMax
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
                            $fd      = $wsTable->get('uid:' . $userId);
                            $expiry  = $orderTable['orderExpiry'];
                            $orderid = substr($_key, strlen('orderId:'));

                            $swoole->push($fd['value'], json_encode([
                                'getOrderStatus' => [
                                    'order_id' => $orderId,
                                    'status'   => $order->status
                                ]
                            ]));

                            $forBetBarRemoval = [
                                'FAILED',
                                'CANCELLED',
                            ];

                            $removalSwtId = "remove-bet-bar-" . $orderId;

                            if (!$ws->exists($removalSwtId)) {
                                if (in_array(strtoupper($order->status), $forBetBarRemoval)) {
                                    if ($expiry == "Now") {
                                        WSForBetBarRemoval::dispatch($fd['value'], $orderId);
                                    } else {
                                        $delay    = substr($row, strlen($row) - 1); // s - Seconds, m - Minutes, h - Hours
                                        $duration = substr($row, 0, strlen($row) - 1);

                                        switch ($delay) {
                                            case 's':
                                                WSForBetBarRemoval::dispatch($fd['value'], $orderId)
                                                    ->delay(now()->addSeconds($duration));
                                                break;

                                            case 'm':
                                                WSForBetBarRemoval::dispatch($fd['value'], $orderId)
                                                    ->delay(now()->addMinutes($duration));
                                                break;

                                            case 'h':
                                                WSForBetBarRemoval::dispatch($fd['value'], $orderId)
                                                    ->delay(now()->addHours($duration));
                                                break;
                                        }
                                    }

                                    $ws->set($removalSwtId, [ 'value' => $orderId, ]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}