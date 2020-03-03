<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WsSelectedSport implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $params)
    {
        $this->userId = $userId;
        $this->sportId = $params[1];
    }

    public function handle()
    {
        $server = app('swoole');
        setUserDefault($this->userId, 'sport', ['sport_id' => $this->sportId]);
        $fd = $server->wsTable->get('uid:' . $this->userId);

        $server->push($fd['value'], json_encode([
            'getSelectedSport' => ['sport_id' => $this->sportId]
        ]));
    }
}
