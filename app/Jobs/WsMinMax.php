<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WsEvents implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $params)
    {
        $this->userId                        = $userId;
        $this->master_event_market_unique_id = $params[1];
    }

    public function handle()
    {
        $topicTable = app('swoole')->topicTable;
        $topicTable->set('userId:' . $this->userId . ':unique:' . uniqid(), [
            'user_id' => $this->userId,
            'topic_name' => 'min-max-' . $this->master_event_market_unique_id
        ]);
    }
}
