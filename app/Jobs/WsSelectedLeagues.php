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
        // Id format for selectedLeaguesTable = 'userSelectedLeagues:' . $userId . ':sId:' . $sportId . ':uniqueId:' . uniqid()
        foreach ($server->wsTable as $key => $row) {
            if (strpos($key, 'userSelectedLeagues:' . $this->userId) === 0) {
                $leagues[] = $row['value'];
            }
        }

        $server->push($fd['value'], json_encode([
            'getSelectedLeagues' => $leagues
        ]));
    }
}
