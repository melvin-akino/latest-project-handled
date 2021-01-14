<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WsOrder implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $params)
    {
        $this->userId  = $userId;
        $this->orderId = $params[1];
    }

    public function handle()
    {
        $topicTable = app('swoole')->topicTable;
        $topicTable->set('userId:' . $this->userId . ':unique:' . uniqid(), [
            'user_id'    => $this->userId,
            'topic_name' => 'order-' . $this->orderId
        ]);

        $toLogs = [
            "class"       => "WsOrder",
            "message"     => "Processed Order (order-" . $this->orderId . ")",
            "module"      => "JOB",
            "status_code" => 200,
        ];
        monitorLog('monitor_jobs', 'info', $toLogs);
    }
}
