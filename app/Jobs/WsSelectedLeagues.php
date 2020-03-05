<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WsSelectedLeagues implements ShouldQueue
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
        $fd = $server->wsTable->get('uid:' . $this->userId);

        $leagues = [];
        foreach ($server->userSelectedLeaguesTable as $key => $row) {
            if (strpos($key, 'userId:' . $this->userId . ':sId:' . $this->sportId) === 0) {
                $leagues[$row['schedule']][] = $row['league_name'];
            }
        }

        $server->push($fd['value'], json_encode([
            'getSelectedLeagues' => $leagues
        ]));
    }
}
