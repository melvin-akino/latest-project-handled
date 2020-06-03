<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class MasterLeague extends Model
{
    use SoftDeletes;

    protected $table = "master_leagues";

    protected $fillable = [
        'sport_id',
        'name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function getIdByName($name)
    {
        $query = self::where('name', $name);

        if ($query->count() == 0) {
            return false;
        }

        return $query->first()->id;
    }

    public static function getLeaguesBySportAndGameShedule(int $sportId, int $providerId, string $gameSchedule)
    {
        return DB::table('master_leagues')
                    ->join('master_events', 'master_events.master_league_id', 'master_leagues.id')
                    ->join('events', 'events.master_event_id', 'master_events.id')
                    ->where('master_leagues.sport_id', $sportId)
                    ->whereNull('master_leagues.deleted_at')
                    ->whereNull('master_events.deleted_at')
                    ->whereNull('events.deleted_at')
                    ->where('master_events.game_schedule', $gameSchedule)
                    ->where('events.provider_id', $providerId)
                    ->groupBy('master_leagues.name')
                    ->select('master_leagues.name as master_league_name',
                        DB::raw('COUNT(master_leagues.name) as match_count'))
                    ->distinct()
                    ->get();
    }
}
