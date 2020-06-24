<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use App\Models\{
    UserSelectedLeague,
    UserProviderConfiguration
};

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
        $providers = UserProviderConfiguration::getProviderIdList($this->userId);
        $leagues = [];
        $userSelectedLeagues = UserSelectedLeague::getSelectedLeagueByUserId($this->userId, $providers);
        array_map(function($userSelectedLeague) use (&$leagues) {
            $leagues[$userSelectedLeague->game_schedule][] = $userSelectedLeague->master_league_name;
        }, $userSelectedLeagues->toArray());

        $server->push($fd['value'], json_encode([
            'getSelectedLeagues' => $leagues
        ]));
    }
}
