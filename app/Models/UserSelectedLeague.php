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
                    ->leftJoin('master_leagues as ml', 'ml.id', 'usl.master_league_id')
                    ->where('usl.sport_id', $sportId)
                    ->where('ml.name', $filters['name'])
                    ->where('usl.game_schedule', $filters['schedule'])
                    ->first();
    }

    public static function getSelectedLeagueByUserId(int $userId, int $providerId)
    {
        return DB::table('user_selected_leagues as usl')
                ->leftJoin('master_leagues as ml', 'ml.id', 'usl.master_league_id')
                ->leftJoin('master_events as me', 'ml.id', 'me.master_league_id')
                ->leftJoin('events as e', 'me.id', 'e.master_event_id')
                ->where('user_id', $userId)
                ->where('e.provider_id', $providerId)
                ->select('usl.game_schedule', 'ml.name as master_league_name')
                ->distinct()
                ->get();
    }
}
