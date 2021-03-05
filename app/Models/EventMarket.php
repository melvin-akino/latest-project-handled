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
                ->select('em.bet_identifier', 'p.alias', 'e.sport_id', 'e.game_schedule', 'e.event_identifier', 'em.odds')
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

    public static function updateProviderEventMarketsByMemUIDWithOdds(string $memUID, $odds)
    {
        return DB::table('event_markets as em')
                 ->leftJoin('master_event_markets as mem', 'mem.id', 'em.master_event_market_id')
                 ->where('mem.master_event_market_unique_id', $memUID)
                 ->update(['em.odds' => $odds]);
    }
}
