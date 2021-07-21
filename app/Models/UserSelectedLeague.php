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

    public static function getSelectedLeagueByUserId(int $userId, int $sportId)
    {
        $primaryProvider = Provider::getIdFromAlias(SystemConfiguration::getSystemConfigurationValue('PRIMARY_PROVIDER')->value);

        return DB::table('user_selected_leagues as usl')
                 ->leftJoin('master_leagues as ml', 'ml.id', 'usl.master_league_id')
                 ->leftJoin('league_groups AS lg', 'lg.master_league_id', 'ml.id')
                 ->leftJoin('leagues AS l', function ($join) use($primaryProvider) {
                    $join->on('l.id', 'lg.league_id');
                 })
                 ->leftJoin('master_events as me', 'ml.id', 'me.master_league_id')
                 ->leftJoin('event_groups as eg', 'me.id', 'eg.master_event_id')
                 ->leftJoin('events as e', 'eg.event_id', 'e.id')
                 ->where('user_id', $userId)
                 ->where('usl.sport_id', $sportId)
                 ->where('e.provider_id', $primaryProvider)
                 ->where('l.provider_id', $primaryProvider)
                 ->whereNull('e.deleted_at')
                 ->select([
                    'usl.game_schedule',
                    'ml.id as master_league_id',
                    DB::raw('COALESCE(ml.name, l.name) as master_league_name')
                 ])
                 ->distinct()
                 ->get();
    }

    public static function getUserSelectedLeague(int $userId, array $filters = [])
    {
        return DB::table('user_selected_leagues')->where('user_id', $userId)
                 ->where('master_league_id', $filters['league_id'])
                 ->where('game_schedule', $filters['schedule'])
                 ->where('sport_id', $filters['sport_id']);
    }

    public static function getSelectedLeagueByAllUsers(array $filters = [])
    {
        return DB::table('user_selected_leagues')
                 ->where('master_league_id', $filters['league_id'])
                 ->where('game_schedule', $filters['schedule'])
                 ->where('sport_id', $filters['sport_id']);
    }

    public static function removeByMasterLeagueNamesAndSchedule(array $names = [], string $schedule = "early")
    {
        return DB::table('user_selected_leagues as usl')
                 ->join('master_leagues as ml', 'ml.id', 'usl.master_league_id')
                 ->whereIn('ml.name', $names)
                 ->where('game_schedule', $schedule)
                 ->delete();
    }
}
