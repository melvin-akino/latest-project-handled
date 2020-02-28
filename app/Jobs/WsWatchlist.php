<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Str;

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
            if (strpos($key, 'userWatchlist:' . $this->userId . ':masterEventUniqueId:') === 0) {
                $uid = str_replace('userWatchlist:' . $this->userId . ':masterEventUniqueId:', '', $key);

                $transformedTable = $server->transformedTable;
                foreach ($transformedTable as $transformed) {
                    unset($transformed['timestamp']);
                    $watchlist[] = $transformed;
                }
                break;
            }
        }

        $server->push($fd['value'], json_encode([
            'getWatchlist' => $watchlist
        ]));
    }
}

//events
//sId:$sportId:pId:$providerId:eventIdentifier:$eventIdentifier
//[ 'name' => 'id',                     'type' => \Swoole\Table::TYPE_INT ],
//[ 'name' => 'event_identifier',       'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
//[ 'name' => 'sport_id',               'type' => \Swoole\Table::TYPE_INT ],
//[ 'name' => 'master_event_unique_id', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
//[ 'name' => 'master_league_name',     'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
//[ 'name' => 'master_home_team_name',  'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
//[ 'name' => 'master_away_team_name',  'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
//[ 'name' => 'game_schedule',          'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
//[ 'name' => 'ref_schedule',           'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
//event markets
//pId:$providerId:meUniqueId:$masterEventUniqueId:memUniqueId:$masterEventMarketUniqueId
//[ 'name' => 'id',                            'type' => \Swoole\Table::TYPE_INT ],
//[ 'name' => 'odd_type_id',                   'type' => \Swoole\Table::TYPE_INT ],
//[ 'name' => 'master_event_market_unique_id', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
//[ 'name' => 'master_event_unique_id',        'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
//[ 'name' => 'event_market_id',               'type' => \Swoole\Table::TYPE_INT ],
//[ 'name' => 'provider_id',                   'type' => \Swoole\Table::TYPE_INT ],
//[ 'name' => 'odds',                          'type' => \Swoole\Table::TYPE_FLOAT ],
//[ 'name' => 'odd_label',                     'type' => \Swoole\Table::TYPE_STRING, 'size' => 10 ],
//[ 'name' => 'bet_identifier',                'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
//[ 'name' => 'is_main',                       'type' => \Swoole\Table::TYPE_INT,    'size' => 1 ],
//[ 'name' => 'market_flag',                   'type' => \Swoole\Table::TYPE_STRING, 'size' => 5 ],
