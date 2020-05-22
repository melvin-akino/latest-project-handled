<?php

namespace App\Jobs;

use App\Models\UserSelectedLeague;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\{Log, DB};
use Exception;

class TransformationLeagueRemoval implements ShouldQueue
{
    use Dispatchable;

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
                $userSelectedLeague = DB::table('user_selected_leagues as usl')
                    ->join('master_leagues as ml', 'ml.id', 'usl.master_league_id')
                    ->where('usl.sport_id', $this->sportId)
                    ->where('ml.master_league_name', $row['name'])
                    ->where('usl.game_schedule', $row['schedule'])
                    ->first();

                if ($userSelectedLeague) {
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
