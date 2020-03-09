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
        foreach ($this->data AS $row) {
            $userSelectedLeague = UserSelectedLeague::where('sport_id', $this->sportId)
                ->where('master_league_name', $row['name'])
                ->where('game_schedule', $row['schedule']);

            if ($userSelectedLeague->exists()) {
                $userSelectedLeague->delete();
            }
        }
    }
}
