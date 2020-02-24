<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WsEvents implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function handle()
    {
        $server = app('swoole');
        $fd = $server->wsTable->get('uid:' . $this->userId);

        $watchlist = [];
        // Id format for watchlistTable = 'userSportLeagueEvents:' . $userId . ':league:' . $league
        foreach ($server->wsTable as $key => $row) {
            if (strpos($key, 'userSportLeagueEvents:' . $this->userId) === 0) {
                $watchlist[str_replace('userSportLeagueEvents:' . $this->userId . ':league:', '', $key)] = json_decode($row['value']);
            }
        }

        $server->push($fd['value'], json_encode([
            'getWatchlist' => $watchlist
        ]));
    }
}
