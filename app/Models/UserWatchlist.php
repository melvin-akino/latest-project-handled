<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserWatchlist extends Model
{
    protected $table = "user_watchlist";

    protected $fillable = [
        'user_id',
        'master_event_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function getByUid(string $uid = '')
    {
        return DB::table('user_watchlist as uw')
                 ->join('master_events as me', 'uw.master_event_id', 'me.id')
                 ->where('master_event_unique_id', $uid)
                 ->select('uw.*');
    }

    public static function getAllLeagueCountByUser()
    {
        $primaryProviderId = Provider::getIdFromAlias(SystemConfiguration::getSystemConfigurationValue('PRIMARY_PROVIDER')->value);

        return DB::table('user_watchlist as uw')
                ->join('master_events as me', 'me.id', 'uw.master_event_id')
                ->join('event_groups as eg', 'eg.master_event_id', 'me.id')
                ->join('events as e', 'e.id', 'eg.event_id')
                ->groupBy('master_league_id', 'user_id', 'e.game_schedule', 'provider_id')
                ->having('e.provider_id', $primaryProviderId)
                ->select('master_league_id', DB::raw('count(master_league_id) as match_count'), 'user_id', 'game_schedule', 'provider_id')
                ->get()
                ->toArray();
    }
}