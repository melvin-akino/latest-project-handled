<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserSelectedLeague extends Model
{
    protected $table = "user_selected_leagues";

    protected $fillable = [
        'user_id',
        'master_league_id',
        'sport_id',
        'game_schedule'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function getSelectedLeague(int $sportId, array $filters = [])
    {
        return DB::table('user_selected_leagues as usl')
                    ->join('master_leagues as ml', 'ml.id', 'usl.master_league_id')
                    ->where('usl.sport_id', $this->sportId)
                    ->where('ml.name', $filters['name'])
                    ->where('usl.game_schedule', $filters['schedule'])
                    ->first();
    }

    public static function getSelectedLeagueByUserId(int $userId)
    {
        return DB::table('user_selected_leagues as usl')
                ->join('master_leagues as ml', 'ml.id', 'usl.master_league_id')
                ->where('user_id', $userId)
                ->select('usl.game_schedule', 'ml.name as master_league_name')
                ->get();
    }
}
