<?php

namespace App\Tasks;

use App\Jobs\WsMinMax;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class TransformKafkaMessageMinMax extends Task
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        $swoole = app('swoole');

        $topics = $swoole->topicsTable;
        $mmr    = $swoole->minMaxRequestsTable;

        foreach ($mmr AS $key => $row) {
            if ($row['market_id'] == $this->data->market_id) {
                $memUID = substr($key, strlen('memUID:'));

                foreach ($topics AS $_key => $_row) {
                    if (strpos($_row['topic_name'], 'min-max-' . $memUID) === 0) {
                        $userId = explode(':', $_key)[1];

                        WsMinMax::dispatch($userId, $memUID);
                    }
                }
            }
        }
    }
}
