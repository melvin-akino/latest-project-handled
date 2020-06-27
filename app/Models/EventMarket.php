<?php

namespace App\Models;

use Carbon\Carbon;
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

    public static function deleteByEventId($eventId)
    {
        return DB::table('event_markets')
          ->where('event_id', $eventId)
          ->update(['deleted_at' => Carbon::now()]);
    }

    public static function deleteByParameters($removeEventMarket)
    {
        return DB::table('event_markets')
                 ->where('market_event_identifier', $removeEventMarket['market_event_identifier'])
                 ->where('odd_type_id', $removeEventMarket['odd_type_id'])
                 ->where('provider_id', $removeEventMarket['provider_id'])
                 ->where('market_flag', $removeEventMarket['market_flag'])
                 ->update(['deleted_at' => Carbon::now(), 'odds' => '']);
    }
}
