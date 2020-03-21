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
        /**
        {
    "request_uid": "77f61545-6756-4463-97a1-b7d8b3824cd9",
    "request_ts": "123456789",
    "command": "bet",
    "sub_command": "transform",
    "data": {
        "provider": "hg",
        "sport": 1,
        "status": "success",
        "market_id": "REH4044819",
        "odds": "1.2",
        "actual_stake": "100.00",
        "actual_to_win": "100.00",
        "score": "1-1",
        "bet_id": "OU1234567890",
        "reason": ""
    }
}
*/
        $swoole = app('swoole');

        $topics = $swoole->topicTable;
        $wsTable = $swoole->wsTable;

        foreach ($topics AS $key => $row) {
            if (strpos($row['topic_name'], 'order-') === 0) {
                $orderId = substr($row['topic_name'], strlen('order-'));
                $messageOrderId = end(explode('-', $this->message->request_uid));

                if ($orderId == $messageOrderId) {
                    Order::updateOrCreate([
                        'id' => $messageOrderId
                    ], [
                        'bet_id' => $this->message->data->bet_id,
                        'reason' => $this->message->data->reason
                    ]);

                    $topics->set('unique:' . uniqid(), [
                        'user_id' => $row['user_id'],
                        'topic_name' => 'open-order-' . $this->message->data->bet_id
                    ]);
                }                
            }
        }
    }
}
