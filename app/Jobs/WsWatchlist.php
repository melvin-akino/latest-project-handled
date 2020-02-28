<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WsWatchlist implements ShouldQueue
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
        // Id format for watchlistTable = 'userWatchlist:' . $userId . ':league:' . $league
        $wsTable = $server->wsTable;
        foreach ($wsTable as $key => $row) {
            if (strpos($key, 'userWatchlist:' . $this->userId) === 0) {
                $watchlist[str_replace('userWatchlist:' . $this->userId . ':masterEventUniqueId:', '', $key)] = $row['value'];
            }
        }

        $server->push($fd['value'], json_encode([
            'getWatchlist' => $watchlist
        ]));
    }
}
