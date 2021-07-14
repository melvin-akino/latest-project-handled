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
        $event         = DB::table('event_markets')->where('mem_uid', $memUID)
                            ->join('events', 'event_markets.event_id', 'events.id')->first();
        $masterEventId = DB::table('event_groups')->select('master_event_id')->where('event_id', $event->event_id)->first();
        $eventIds      = DB::table('event_groups')->where('master_event_id', $masterEventId->master_event_id)->pluck('event_id');
        $query         = DB::table('event_markets as em')
            ->leftJoin('providers as p', 'p.id', 'em.provider_id')
            ->leftJoin('events as e', 'e.id', 'em.event_id')
            ->leftJoin('event_groups as eg', 'eg.event_id', 'e.id')
            ->leftJoin('master_events as me', 'me.id', 'eg.master_event_id')
            ->whereNull('em.deleted_at')
            ->whereIn('em.event_id', $eventIds)
            ->where('em.market_flag', $event->market_flag)
            ->where('em.odd_type_id', $event->odd_type_id)
            ->where('em.odd_label', $event->odd_label)
            ->where('e.game_schedule', $event->game_schedule)
            ->select('em.bet_identifier', 'p.alias', 'e.sport_id', 'e.game_schedule', 'e.event_identifier', 'em.odds', 'em.mem_uid')
            ->get();

        return $query;
    }

    public static function getProviderEventMarketsByMemUID(string $memUID)
    {
        return DB::table('event_markets')
                 ->where('mem_uid', $memUID)
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
                 ->update(['deleted_at' => Carbon::now(), 'odds' => 0]);
    }

    public static function getMarketDetailsByListOfMarketIds(array $marketIds = [])
    {
        $sqlTable = "SELECT
                em.bet_identifier,
                em.market_flag,
                em.odd_type_id,
                me.score,
                mem.master_event_market_unique_id,
                me.master_event_unique_id,
                ml.name as master_league_name,
                mth.name as master_team_home_name,
                mta.name as master_team_away_name
            FROM event_markets as em
            JOIN master_event_markets as mem ON em.master_event_market_id = mem.id
            JOIN master_events as me ON mem.master_event_id = me.id
            JOIN master_leagues as ml ON ml.id = me.master_league_id
            JOIN master_teams as mth ON mth.id = me.master_team_home_id
            JOIN master_teams as mta ON mta.id = me.master_team_away_id
            WHERE
                em.bet_identifier IN ('" . implode("', '", $marketIds) . "')

        ";

        $sql = "UPDATE orders SET
                market_flag = marketTable.market_flag,
                odd_type_id = marketTable.odd_type_id,
                final_score = CASE WHEN settled_date is null THEN null ELSE marketTable.score END,
                master_event_market_unique_id = marketTable.master_event_market_unique_id,
                master_event_unique_id = marketTable.master_event_unique_id,
                master_league_name = marketTable.master_league_name,
                master_team_home_name = marketTable.master_team_home_name,
                master_team_away_name = marketTable.master_team_away_name
            FROM (" . $sqlTable . ") as marketTable
            WHERE market_id = marketTable.bet_identifier
        ";

        DB::update($sql);
    }

    public static function updateProviderEventMarketsByMemUIDWithOdds(string $betID, $odds)
    {
        return DB::table('event_markets AS em')
            ->where('bet_identifier', $betID)
            ->update([ 'em.odds' => $odds ]);
    }

    public static function hasActiveEventMarketWithSamePosition($eventMarketDetails) {
        return DB::table('event_markets')
                 ->where('event_id', $eventMarketDetails->event_id)
                 ->where('odd_type_id', $eventMarketDetails->odd_type_id)
                 ->where('odd_label', $eventMarketDetails->odd_label)
                 ->where('market_flag', $eventMarketDetails->market_flag)
                 ->whereNull('deleted_at')
                 ->first();
    }

    public static function getOddTypeByMemUID(string $memUID)
    {
        return DB::table('event_markets as em')
                ->join('odd_types as ot', 'ot.id', 'em.odd_type_id')
                ->where('mem_uid', $memUID)
                ->select('ot.id', 'ot.type', 'mem_uid')
                ->first();
    }
}
