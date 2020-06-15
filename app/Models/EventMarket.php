<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Support\Facades\DB;

class EventMarket extends Model
{
    use SoftDeletes;

    protected $table = "event_markets";

    protected $fillable = [
        'master_event_market_id',
        'event_id',
        'odd_type_id',
        'odds',
        'odd_label',
        'bet_identifier',
        'is_main',
        'market_flag',
        'provider_id',
        'deleted_at',
        'market_event_identifier',
    ];

    public static function getEventMarketByMemUID(string $memUID)
    {
        return DB::table('event_markets as em')
                ->leftJoin('providers as p', 'p.id', 'em.provider_id')
                ->leftJoin('events as e', 'e.id', 'em.event_id')
                ->leftJoin('master_events as me', 'me.id', 'e.master_event_id')
                ->leftJoin('master_event_markets as mem', 'mem.id', 'em.master_event_market_id')
                ->where('mem.master_event_market_unique_id',$memUID)
                ->select('em.bet_identifier', 'p.alias', 'e.sport_id', 'me.game_schedule', 'e.event_identifier', 'em.odds')
                ->distinct()
                ->get();
    }

    public static function getProviderEventMarketsByMemUID(string $memUID)
    {
        return DB::table('event_markets as em')
                 ->leftJoin('master_event_markets as mem', 'mem.id', 'em.master_event_market_id')
                 ->where('mem.master_event_market_unique_id', $memUID)
                 ->distinct()
                 ->first();
    }
}
