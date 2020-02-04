<?php

namespace App\Models;

use App\Models\{Sport, UserConfiguration};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Leagues extends Model
{
    public static function getLeagues(int $sportType = null)
    {
        /**

        protected $table = "match_link";

        if ($sportType == null) {
            $sportType = Sport::where('sport', 'Soccer')->first()->id;
        }

        $data = self::groupBy('game_type', 'league_name', 'game_schedule', DB::raw('COUNT(*) AS match_count'))
            ->where('game_type', $sportType)
            ->where()
            ->orderBy(DB::raw("(CASE WHEN game_schedule = 'IN-PLAY' THEN 1 WHEN game_schedule = 'TODAY' THEN 2 WHEN game_schedule = 'EARLY' THEN 3 END)"), 'asc')
            ->orderBy('league_name', 'asc')
            ->get()->toArray();

        foreach ($data AS $key => $row) {
            $data[$row->game_schedule][] = [$row->league_name => $row->match_count];
        }

        **/

        $data = [
            'IN-PLAY' => [
                [ 'Italian Serie B'         => 2 ],
                [ 'UEFA Champions League'   => 1 ],
            ],
            'TODAY'  => [
                [ 'Argentina Superliga'     => 2 ],
                [ 'Chinese Super League'    => 4 ],
                [ 'French Ligue 2'          => 1 ],
            ],
            'EARLY'  => [
                [ 'Argentina Superliga'     => 12 ],
                [ 'English Premier League'  => 3 ],
                [ 'German Bundesliga 2'     => 6 ],
            ],
        ];

        return $data;
    }
}
