<?php

namespace App\Tasks;

use App\Models\UserSelectedLeague;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;
use Exception;

class TransformationLeagueRemoval extends Task
{
    protected $data;
    protected $sportId;

    public function __construct(array $data, int $sportId)
    {
        $this->data    = $data;
        $this->sportId = $sportId;
    }

    public function handle()
    {
        try {
            $userSelectedLeaguesTable = app('swoole')->userSelectedLeaguesTable;
            foreach ($this->data AS $row) {
                $userSelectedLeague = UserSelectedLeague::where('sport_id', $this->sportId)
                    ->where('master_league_name', $row['name'])
                    ->where('game_schedule', $row['schedule']);

                if ($userSelectedLeague->exists()) {
                    $userSelectedLeague->delete();
                    foreach ($userSelectedLeaguesTable as $key => $userSelected) {
                        if ($userSelected['league_name'] == $row['name']) {
                            $userSelectedLeaguesTable->del($key);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
