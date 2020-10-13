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

    public static function getIdByName($name)
    {
        $query = self::where('name', $name);

        if ($query->count() == 0) {
            return false;
        }

        return $query->first()->id;
    }

    public static function getLeaguesBySportAndGameSchedule(int $sportId, int $userId, array $userProviderIds, string $gameSchedule, string $keyword = null)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        if($keyword) {
            $columns = [DB::raw("'league' as type"), 'master_league_name as data','master_league_name as label'];
        } else {
            $columns = ['master_league_name', DB::raw('COUNT(master_league_name) AS match_count')];
        }

        $subquery = DB::table('master_leagues as ml')
            ->leftJoin('sports as s', 's.id', 'ml.sport_id')
            ->leftJoin('master_events as me', 'ml.id', 'me.master_league_id')
            ->join('events as e', 'me.id', 'e.master_event_id')
            ->leftJoin('master_event_markets as mem', 'me.id', 'mem.master_event_id')
            ->join('event_markets AS em', function ($join) {
                $join->on('mem.id', 'em.master_event_market_id');
                $join->on('e.id', 'em.event_id');
            })
            ->where('s.id', $sportId)
            ->whereNull('me.deleted_at')
            ->whereNull('e.deleted_at')
            ->whereNull('ml.deleted_at')
            ->whereNull('em.deleted_at')
            ->where('e.missing_count', '<=', $maxMissingCount)
            ->when($gameSchedule, function($query, $gameSchedule) {
                return $query->where('me.game_schedule', $gameSchedule);
            })
            ->whereIn('e.provider_id', $userProviderIds)
            ->whereIn('em.provider_id', $userProviderIds)
            ->where('mem.is_main', true)
            ->whereNotIn('me.id', function($query) use ($userId) {
                $query->select('master_event_id')->from('user_watchlist')->where('user_id', $userId);
            })
            ->select('ml.name as master_league_name', 'me.id')
            ->groupBy('ml.name', 'me.id');

        return DB::table(DB::raw("({$subquery->toSql()}) AS leagues_list"))
            ->mergeBindings($subquery)
            ->select($columns)
            ->when($keyword, function($query, $keyword) {
                return $query->where('master_league_name', 'ILIKE', $keyword . '%');
            })
            ->groupBy('master_league_name');
    }

    public static function getLeagueDetailsByName(string $league)
    {
        return DB::table('master_leagues')->where('name', $league)->first();
    }
}
