<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

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
        $userSelectedLeagues = DB::table('user_selected_leagues')
                            ->where('sport_id', $this->sportId)
                            ->where('user_id', $this->userId)
                            ->get();
        array_map(function($userSelectedLeague) use (&$leagues) {
            $leagues[$userSelectedLeague->game_schedule][] = $userSelectedLeague->master_league_name;
        }, $userSelectedLeagues->toArray());

        $server->push($fd['value'], json_encode([
            'getSelectedLeagues' => $leagues
        ]));
    }
}
