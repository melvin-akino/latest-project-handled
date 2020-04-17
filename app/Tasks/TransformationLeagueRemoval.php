<?php

namespace App\Tasks;

use App\Models\UserSelectedLeague;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class TransformationLeagueRemoval extends Task
{
    protected $data;
    protected $sportId;

    public function __construct(array $data, int $sportId)
    {
        $this->data   = $data;
        $this->sportId = $sportId;
    }

    public function handle()
    {
        $userSelectedLeaguesTable = app('swoole')->userSelectedLeaguesTable;
        foreach ($this->data AS $row) {
            $userSelectedLeague = UserSelectedLeague::where('sport_id', $this->sportId)
                ->where('master_league_name', $row['name'])
                ->where('game_schedule', $row['schedule']);

            if ($userSelectedLeague->exists()) {
                foreach ($userSelectedLeaguesTable as $key => $userSelected) {
                    if (strpos('userId:' . $userSelectedLeague->user_id . ':sId:' . $userSelectedLeague->sport_id . ':schedule:' . $userSelectedLeague->game_schedule) === 0) {
                        if ($userSelected['league_name'] == $userSelectedLeague->master_league_name) {
                            $userSelectedLeaguesTable->del($key);
                        }
                    }
                }

                $userSelectedLeague->delete();
            }
        }
    }
}
