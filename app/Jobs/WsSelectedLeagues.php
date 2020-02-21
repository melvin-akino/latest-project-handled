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
        // Id format for selectedLeaguesTable = 'uid:' . $userId . ':sportId:' . $sportId . ':master_league_id:' . $masterLeagueId'
        foreach ($server->selectedLeaguesTable as $key => $row) {
            if (strpos($key, 'uid:' . $this->userId . ':sportId:' . $this->sportId) === 0) {
                $leagues[] = [ 'league_id' => $row['master_league_id']];
            }
        }

        $server->push($fd['value'], json_encode([
            'getSelectedLeagues' => $leagues
        ]));
    }
}
