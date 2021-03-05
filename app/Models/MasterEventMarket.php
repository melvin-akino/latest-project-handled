<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MasterEventMarket extends Model
{
    protected $table = "master_event_markets";

    protected $fillable = [
        'master_event_id',
        'odd_type_id',
        'master_event_market_unique_id',
        'is_main',
        'market_flag',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function getExistingMemUID(string $masterEventId, int $oddTypeId, string $oddLabel = '', string $marketFlag)
    {
        return DB::table('master_event_markets as mem')
                 ->join('event_markets as em', 'em.master_event_market_id', 'mem.id')
                 ->where('mem.odd_type_id', $oddTypeId)
                 ->where('odd_label', $oddLabel)
                 ->where('mem.market_flag', $marketFlag)
                 ->where('mem.master_event_id', $masterEventId)
                 ->first();
    }

    public static function getSelectedMarkets(int $masterLeagueId, string $schedule, int $sportId)
    {
        return DB::table('master_event_markets as mem')
                ->leftJoin('master_events as me', 'me.id', 'mem.master_event_id')
                ->leftJoin('master_leagues as ml', 'ml.id', 'me.master_league_id')
                ->whereNull('me.deleted_at')
                ->whereNull('ml.deleted_at')
                ->where('ml.id', $masterLeagueId)
                ->where('e.game_schedule', $schedule)
                ->where('me.sport_id', $sportId)
                ->select('mem.master_event_market_unique_id', 'me.master_event_unique_id')
                ->get()
                ->pluck('master_event_unique_id', 'master_event_market_unique_id');
    }
}
