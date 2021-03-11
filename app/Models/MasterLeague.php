<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};
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

    public static function getLeagueDetailsByName($name)
    {
        $query = DB::table('master_leagues as ml')
                    ->join('league_groups as lg', 'lg.master_league_id', 'ml.id')
                    ->join('leagues as l', 'l.id', 'lg.league_id')
                    ->where('ml.name', $name)
                    ->orWhere('l.name', $name)
                    ->select([
                        'ml.id as id',
                        DB::raw('COALESCE(ml.name, l.name) as name')
                    ]);

        if ($query->count() == 0) {
            return false;
        }

        return $query->first();
    }

    public static function getLeaguesBySportAndGameSchedule(int $sportId, int $userId, array $userProviderIds, string $gameSchedule, string $keyword = null)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        if($keyword) {
            $columns = [DB::raw("'league' as type"), 'master_league_name as data','master_league_name as label'];
        } else {
            $columns = ['master_league_name', DB::raw('COUNT(master_league_name) AS match_count')];
        }

        $subquery = DB::table('trade_window')
            ->where('sport_id', $sportId)
            ->where('missing_count', '<=', $maxMissingCount)
            ->when($gameSchedule, function($query, $gameSchedule) {
                return $query->where('game_schedule', $gameSchedule);
            })
            ->whereNotIn('master_event_id', function($query) use ($userId) {
                $query->select('master_event_id')->from('user_watchlist')->where('user_id', $userId);
            })
            ->select('master_league_name', 'master_event_id')
            ->groupBy('master_league_name', 'master_event_id');

        return DB::table(DB::raw("({$subquery->toSql()}) AS leagues_list"))
            ->mergeBindings($subquery)
            ->select($columns)
            ->when($keyword, function($query, $keyword) {
                return $query->where('master_league_name', 'ILIKE', str_replace('%', '^', $keyword) . '%');
            })
            ->groupBy('master_league_name');
    }

    public static function getLeagueNameDetails(string $league)
    {
        return DB::table('master_leagues AS ml')
            ->join('league_groups AS lg', 'lg.master_league_id', 'ml.id')
            ->join('leagues AS l', 'l.id', 'lg.league_id')
            ->where(DB::raw('COALESCE(ml.name, l.name)'), $league);
    }
}
