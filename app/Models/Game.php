<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Game extends Model
{

    public static function updateOddsData(array $marketOdds = [], int $providerId)
    {
        return DB::table('event_markets as em')
                ->join('master_event_markets as mem', 'mem.id', 'em.master_event_market_id')
                ->where('mem.master_event_market_unique_id', $marketOdds['market_id'])
                ->where('em.provider_id', $providerId)
                ->update([
                    'em.odds' => $marketOdds['odds']
                ]);
    }

    public static function getGameDetails(int $masterLeagueId, string $schedule = 'early')
    {
        return DB::table('master_leagues as ml')
                ->join('sports as s', 's.id', 'ml.sport_id')
                ->join('master_events as me', 'me.master_league_id', 'ml.id')
                ->join('master_teams as mth', 'mth.id', 'me.master_team_home_id')
                ->join('master_teams as mta', 'mta.id', 'me.master_team_home_id')
                ->join('master_event_markets as mem', 'mem.master_event_id', 'me.id')
                ->join('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                ->join('event_markets as em', 'em.master_event_market_id', 'mem.id')
                ->select('ml.sport_id', 'ml.name as master_league_name', 's.sport',
                    'me.master_event_unique_id', 'mth.name as master_home_team_name', 'mta.name as master_away_team_name',
                    'me.ref_schedule', 'me.game_schedule', 'me.score', 'me.running_time',
                    'me.home_penalty', 'me.away_penalty', 'mem.odd_type_id', 'mem.master_event_market_unique_id', 'mem.is_main', 'mem.market_flag',
                    'ot.type', 'em.odds', 'em.odd_label', 'em.provider_id', 'em.bet_identifier')
                ->where('ml.id', $masterLeagueId)
                ->where('me.game_schedule', $schedule)
                ->where('mem.is_main', true)
                ->whereNull('me.deleted_at')
                ->distinct()->get();
    }

    public static function getWatchlistGameDetails(int $userId)
    {
        return $transformed = DB::table('master_leagues as ml')
            ->join('sports as s', 's.id', 'ml.sport_id')
            ->join('master_events as me', 'me.master_league_id', 'ml.id')
            ->join('master_event_markets as mem', 'mem.master_event_id', 'me.id')
            ->join('master_teams as mth', 'mth.id', 'me.master_team_home_id')
            ->join('master_teams as mta', 'mta.id', 'me.master_team_home_id')
            ->join('odd_types as ot', 'ot.id', 'mem.odd_type_id')
            ->join('event_markets as em', 'em.master_event_market_id', 'mem.id')
            ->join('user_watchlist as uw', 'uw.master_event_id', 'me.id')
            ->select('ml.sport_id', 'ml.name as master_league_name', 's.sport',
                'me.master_event_unique_id', 'mth.name as master_home_team_name', 'mta.name as master_away_team_name',
                'me.ref_schedule', 'me.game_schedule', 'me.score', 'me.running_time',
                'me.home_penalty', 'me.away_penalty', 'mem.odd_type_id', 'mem.master_event_market_unique_id',
                'mem.is_main', 'mem.market_flag',
                'ot.type', 'em.odds', 'em.odd_label', 'em.provider_id')
            ->where('uw.user_id', $userId)
            ->where('mem.is_main', true)
            ->distinct()->get();
    }

    public static function getOtherMarketSpreadDetails(array $fields = [])
    {
        return DB::table('event_markets AS em')
                ->join('master_event_markets AS mem', 'mem.id', 'em.master_event_market_id')
                ->join('master_events as me', 'me', 'mem.master_event_id')
                ->where('mem.master_event_unique_id', $fields['master_event_unique_id'])
                ->where('mem.odd_type_id', $fields['odd_type_id'])
                ->where('em.market_flag', $fields['market_flag'])
                ->where('em.provider_id', $fields['provider_id'])
                ->where('me.game_schedule', $fields['game_schedule'])
                ->whereNull('em.deleted_at')
                ->distinct()
                ->get(
                    [
                        'mem.master_event_market_unique_id',
                        'em.odds',
                        'em.odd_label',
                        'em.is_main'
                    ]
                );
    }

    public static function getmasterEventByMarketId(string $marketId)
    {
        return DB::table('master_events AS me')
                    ->join('master_leagues as ml', 'ml.id', 'me.master_league_id')
                    ->join('master_teams as mth', 'mth.id', 'me.master_team_home_id')
                    ->join('master_teams as mta', 'mta.id', 'me.master_team_home_id')
                    ->join('master_event_markets AS mem', 'me.id', 'mem.master_event_id')
                    ->join('event_markets AS em', 'em.master_event_market_id', 'mem.id')
                    ->join('odd_types AS ot', 'ot.id', 'mem.odd_type_id')
                    ->whereNull('me.deleted_at')
                    ->where('mem.master_event_market_unique_id', $marketId)
                    ->select([
                        'me.sport_id',
                        'me.master_event_unique_id',
                        'ml.name as master_league_name',
                        'mth.name as master_home_team_name',
                        'mta.name as master_away_team_name',
                        'me.game_schedule',
                        'me.running_time',
                        'me.score',
                        'mem.master_event_market_unique_id',
                        'mem.is_main',
                        'mem.market_flag',
                        'mem.odd_type_id',
                        'em.bet_identifier',
                        'em.provider_id',
                        'em.odds',
                        'em.odd_label',
                        'ot.type AS column_type',
                    ])
                    ->first();
    }

    public static function getBetSlipLogs(int $userId, string $memUID)
    {
        return DB::table('bet_slip_logs')
                ->where(function ($cond) {
                    $cond->where('user_id', 0)
                        ->orWhere('user_id', $userId);
                })
                ->where('memuid', $memUID)
                ->orderBy('timestamp', 'desc')
                ->limit(20)
                ->get();
    }

    public static function getSelectedLeagueEvents(int $userId)
    {
        return DB::table('master_leagues as ml')
                    ->join('sports as s', 's.id', 'ml.sport_id')
                    ->join('master_events as me', 'me.master_league_id', 'ml.id')
                    ->join('master_teams as mth', 'mth.id', 'me.master_team_home_id')
                    ->join('master_teams as mta', 'mta.id', 'me.master_team_home_id')
                    ->join('master_event_markets as mem', 'mem.master_event_id', 'me.id')
                    ->join('event_markets as em', 'em.master_event_market_id', 'mem.id')
                    ->join('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                    ->join('user_selected_leagues AS sl', 'ml.id', 'sl.master_league_id')
                    ->where('sl.game_schedule', DB::raw('me.game_schedule'))
                    ->where('sl.user_id', $userId)
                    ->whereNull('me.deleted_at')
                    ->where('mem.is_main', true)
                    ->whereNull('ml.deleted_at')
                    ->select([
                        'ml.sport_id',
                        'ml.name as master_league_name',
                        's.sport',
                        'me.master_event_unique_id',
                        'mth.name as master_home_team_name',
                        'mta.name as master_away_team_name',
                        'me.ref_schedule',
                        'me.game_schedule',
                        'me.score',
                        'me.running_time',
                        'me.home_penalty',
                        'me.away_penalty',
                        'mem.odd_type_id',
                        'mem.master_event_market_unique_id',
                        'mem.is_main',
                        'mem.market_flag',
                        'ot.type',
                        'em.odds',
                        'em.odd_label',
                        'em.provider_id',
                        'em.bet_identifier',
                    ])
                    ->distinct()->get();
    }

    public static function getWatchlistEvents(int $userId)
    {
        return DB::table('master_leagues as ml')
                    ->join('sports as s', 's.id', 'ml.sport_id')
                    ->join('master_events as me', 'me.master_league_id', 'ml.id')
                    ->join('master_teams as mth', 'mth.id', 'me.master_team_home_id')
                    ->join('master_teams as mta', 'mta.id', 'me.master_team_home_id')
                    ->join('master_event_markets as mem', 'mem.master_event_id', 'me.id')
                    ->join('event_markets as em', 'em.master_event_market_id', 'mem.id')
                    ->join('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                    ->join('user_watchlist AS uw', 'me.id', 'uw.master_event_id')
                    ->where('uw.user_id', $userId)
                    ->whereNull('me.deleted_at')
                    ->whereNull('ml.deleted_at')
                    ->where('mem.is_main', true)
                    ->select([
                        'ml.sport_id',
                        'ml.name as master_league_name',
                        's.sport',
                        'me.master_event_unique_id',
                        'mth.name as master_home_team_name',
                        'mta.name as master_away_team_name',
                        'me.ref_schedule',
                        'me.game_schedule',
                        'me.score',
                        'me.running_time',
                        'me.home_penalty',
                        'me.away_penalty',
                        'mem.odd_type_id',
                        'mem.master_event_market_unique_id',
                        'mem.is_main',
                        'mem.market_flag',
                        'ot.type',
                        'em.odds',
                        'em.odd_label',
                        'em.provider_id',
                        'em.bet_identifier',
                    ])
                    ->distinct()->get();
    }

    public static function getOtherMarketsByMemUID(string $memUID)
    {
        return DB::table('master_events as me')
                ->join('sports as s', 's.id', 'me.sport_id')
                ->join('master_event_markets as mem', 'mem.master_event_id', 'me.id')
                ->join('event_markets as em', 'em.master_event_market_id', 'mem.id')
                ->join('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                ->whereNull('me.deleted_at')
                ->where('mem.is_main', false)
                ->where('me.master_event_unique_id', $memUID)
                ->select([
                    's.sport',
                    'me.master_event_unique_id',
                    'me.master_home_team_name',
                    'me.master_away_team_name',
                    'me.ref_schedule',
                    'me.game_schedule',
                    'me.score',
                    'me.running_time',
                    'me.home_penalty',
                    'me.away_penalty',
                    'mem.odd_type_id',
                    'mem.master_event_market_unique_id',
                    'mem.is_main',
                    'mem.market_flag',
                    'ot.type',
                    'em.odds',
                    'em.odd_label',
                    'em.provider_id',
                    'em.event_identifier'
                ])
                ->distinct()->get();
    }

    public static function searchSuggestion(string $key)
    {
        return DB::table('search_suggestions')
                ->where('label', 'ILIKE', '%' . trim($key) . '%');
    }
}
