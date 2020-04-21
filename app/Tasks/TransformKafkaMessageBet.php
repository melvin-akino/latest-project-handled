<?php

namespace App\Tasks;

use App\Jobs\WSOrderStatus;
use App\Models\Order;
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
        $payloadsTable  = $swoole->payloadsTable;

        foreach ($topics AS $key => $row) {
            if (strpos($row['topic_name'], 'order-') === 0) {
                $orderId         = substr($row['topic_name'], strlen('order-'));
                $requestUIDArray = explode('-', $this->message->request_uid);
                $messageOrderId  = end($requestUIDArray);

                if ($orderId == $messageOrderId) {
                    $status = strtoupper($this->message->data->status);
                    $order = Order::updateOrCreate([
                        'id' => $messageOrderId
                    ], [
                        'bet_id' => $this->message->data->bet_id,
                        'reason' => $this->message->data->reason,
                        'status' => $status
                    ]);

                    $betSelectionArray = explode('@ ', $order->bet_selection);
                    $order->bet_selection = $betSelectionArray[0] . '@ ' .$order->odds;
                    $order->save()

                    WSOrderStatus::dispatch(
                        $row['user_id'],
                        $orderId,
                        $status,
                        $this->message->data->odds,
                        $ordersTable['orderId:' . $orderId]['orderExpiry'],
                        $ordersTable['orderId:' . $orderId]['created_at']
                    );

                    $topics->set('unique:' . uniqid(), [
                        'user_id'    => $row['user_id'],
                        'topic_name' => 'open-order-' . $this->message->data->bet_id
                    ]);

                    $ordersTable['orderId:' . $orderId]['bet_id'] = $this->message->data->bet_id;

                    $payloadsSwtId = implode(':', [
                        "place-bet-" . $orderId,
                        "uId:"       . $row['user_id'],
                        "mId:"       . $order->market_id
                    ]);
                    if ($payloadsTable->exists($payloadsSwtId)) {
                        $payloadsTable->del($payloadsSwtId);
                    }
                }
            }
        }
    }
}
