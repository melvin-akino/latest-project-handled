<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Game extends Model
{
    public static function updateOddsData(array $marketOdds = [], int $providerId)
    {
        return DB::table('event_markets as em')
                 ->leftJoin('master_event_markets as mem', 'mem.id', 'em.master_event_market_id')
                 ->where('mem.master_event_market_unique_id', $marketOdds['market_id'])
                 ->where('em.provider_id', $providerId)
                 ->update([
                     'em.odds' => $marketOdds['odds']
                 ]);
    }

    public static function getGameDetails(int $masterLeagueId, string $schedule = 'early')
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        return DB::table('master_leagues as ml')
                 ->leftJoin('sports as s', 's.id', 'ml.sport_id')
                 ->leftJoin('master_events as me', 'me.master_league_id', 'ml.id')
                 ->leftJoin('events as e', 'e.master_event_id', 'me.id')
                 ->leftJoin('master_teams as mth', 'mth.id', 'me.master_team_home_id')
                 ->leftJoin('master_teams as mta', 'mta.id', 'me.master_team_away_id')
                 ->leftJoin('master_event_markets as mem', 'mem.master_event_id', 'me.id')
                 ->leftJoin('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                 ->leftJoin('event_markets as em', function ($join) {
                     $join->on('em.master_event_market_id', '=', 'mem.id');
                     $join->on('em.event_id', '=', 'e.id');
                 })
                 ->select('ml.sport_id', 'ml.name as master_league_name', 's.sport', 'e.master_event_id',
                     'me.master_event_unique_id', 'mth.name as master_home_team_name', 'mta.name as master_away_team_name',
                     'me.ref_schedule', 'me.game_schedule', 'me.score', 'me.running_time',
                     'me.home_penalty', 'me.away_penalty', 'mem.odd_type_id', 'mem.master_event_market_unique_id', 'mem.is_main', 'mem.market_flag',
                     'ot.type', 'em.odds', 'em.odd_label', 'em.provider_id', 'em.bet_identifier')
                 ->where('ml.id', $masterLeagueId)
                 ->where('me.game_schedule', $schedule)
                 ->where('mem.is_main', true)
                 ->whereNull('me.deleted_at')
                 ->whereNull('e.deleted_at')
                 ->whereNull('ml.deleted_at')
                 ->where('e.missing_count', '<=', $maxMissingCount)
                 ->get();
    }

    public static function providersOfEvents(int $masterEventId, array $userProviderIds)
    {
        return DB::table('master_events as me')
                 ->leftJoin('events as e', 'e.master_event_id', 'me.id')
                 ->leftJoin('providers as p', 'p.id', 'e.provider_id')
                 ->where('e.master_event_id', $masterEventId)
                 ->whereNull('me.deleted_at')
                 ->whereNull('e.deleted_at')
                 ->whereIn('p.id', $userProviderIds)
                 ->select('p.id', 'p.alias as provider')
                 ->distinct();
    }

    public static function getWatchlistGameDetails(int $userId)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        return DB::table('master_leagues as ml')
                 ->leftJoin('sports as s', 's.id', 'ml.sport_id')
                 ->leftJoin('master_events as me', 'me.master_league_id', 'ml.id')
                 ->leftJoin('events as e', 'e.master_event_id', 'me.id')
                 ->leftJoin('master_event_markets as mem', 'mem.master_event_id', 'me.id')
                 ->leftJoin('master_teams as mth', 'mth.id', 'me.master_team_home_id')
                 ->leftJoin('master_teams as mta', 'mta.id', 'me.master_team_away_id')
                 ->leftJoin('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                 ->leftJoin('event_markets as em', function ($join) {
                     $join->on('em.master_event_market_id', '=', 'mem.id');
                     $join->on('em.event_id', '=', 'e.id');
                 })
                 ->leftJoin('user_watchlist as uw', 'uw.master_event_id', 'me.id')
                 ->select('ml.sport_id', 'ml.name as master_league_name', 's.sport',
                     'me.master_event_unique_id', 'mth.name as master_home_team_name', 'mta.name as master_away_team_name',
                     'me.ref_schedule', 'me.game_schedule', 'me.score', 'me.running_time',
                     'me.home_penalty', 'me.away_penalty', 'mem.odd_type_id', 'mem.master_event_market_unique_id',
                     'mem.is_main', 'mem.market_flag',
                     'ot.type', 'em.odds', 'em.odd_label', 'em.provider_id', 'em.bet_identifier', 'e.master_event_id')
                 ->where('uw.user_id', $userId)
                 ->where('mem.is_main', true)
                 ->where('e.missing_count', '<=', $maxMissingCount)
                 ->distinct()->get();
    }

    public static function getOtherMarketSpreadDetails(array $fields = [])
    {
        return DB::table('event_markets AS em')
                 ->leftJoin('master_event_markets AS mem', 'mem.id', 'em.master_event_market_id')
                 ->leftJoin('master_events as me', 'me.id', 'mem.master_event_id')
                 ->where('mem.master_event_id', $fields['master_event_id'])
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
                 ->leftJoin('master_leagues as ml', 'ml.id', 'me.master_league_id')
                 ->leftJoin('master_teams as mth', 'mth.id', 'me.master_team_home_id')
                 ->leftJoin('master_teams as mta', 'mta.id', 'me.master_team_away_id')
                 ->leftJoin('master_event_markets AS mem', 'me.id', 'mem.master_event_id')
                 ->leftJoin('event_markets AS em', 'em.master_event_market_id', 'mem.id')
                 ->leftJoin('odd_types AS ot', 'ot.id', 'mem.odd_type_id')
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
                 ->where(function ($cond) use ($userId) {
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
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        return DB::table('master_leagues as ml')
                 ->leftJoin('sports as s', 's.id', 'ml.sport_id')
                 ->leftJoin('master_events as me', 'me.master_league_id', 'ml.id')
                 ->leftJoin('events as e', 'e.master_event_id', 'me.id')
                 ->leftJoin('master_teams as mth', 'mth.id', 'me.master_team_home_id')
                 ->leftJoin('master_teams as mta', 'mta.id', 'me.master_team_away_id')
                 ->leftJoin('master_event_markets as mem', 'mem.master_event_id', 'me.id')
                 ->leftJoin('event_markets as em', function ($join) {
                     $join->on('em.master_event_market_id', '=', 'mem.id');
                     $join->on('em.event_id', '=', 'e.id');
                 })
                 ->leftJoin('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                 ->leftJoin('user_selected_leagues AS sl', 'ml.id', 'sl.master_league_id')
                 ->where('sl.game_schedule', DB::raw('me.game_schedule'))
                 ->where('sl.user_id', $userId)
                 ->where('mem.is_main', true)
                 ->whereNull('me.deleted_at')
                 ->whereNull('e.deleted_at')
                 ->whereNull('ml.deleted_at')
                 ->where('e.missing_count', '<=', $maxMissingCount)
                 ->select([
                     'ml.sport_id',
                     'ml.name as master_league_name',
                     's.sport',
                     'e.master_event_id',
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
                     'e.provider_id',
                     'em.bet_identifier',
                 ])
                 ->get();
    }

    public static function getWatchlistEvents(int $userId)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        return DB::table('master_leagues as ml')
                 ->leftJoin('sports as s', 's.id', 'ml.sport_id')
                 ->leftJoin('master_events as me', 'me.master_league_id', 'ml.id')
                 ->leftJoin('events as e', 'e.master_event_id', 'me.id')
                 ->leftJoin('master_teams as mth', 'mth.id', 'me.master_team_home_id')
                 ->leftJoin('master_teams as mta', 'mta.id', 'me.master_team_away_id')
                 ->leftJoin('master_event_markets as mem', 'mem.master_event_id', 'me.id')
                 ->leftJoin('event_markets as em', function ($join) {
                     $join->on('em.master_event_market_id', '=', 'mem.id');
                     $join->on('em.event_id', '=', 'e.id');
                 })
                 ->leftJoin('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                 ->leftJoin('user_watchlist AS uw', 'me.id', 'uw.master_event_id')
                 ->where('uw.user_id', $userId)
                 ->whereNull('me.deleted_at')
                 ->whereNull('ml.deleted_at')
                 ->where('mem.is_main', true)
                 ->where('e.missing_count', '<=', $maxMissingCount)
                 ->select([
                     'ml.sport_id',
                     'ml.name as master_league_name',
                     's.sport',
                     'e.master_event_id',
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
                     'e.provider_id',
                     'em.bet_identifier',
                 ])
                 ->get();
    }

    public static function getOtherMarketsByMemUID(string $meUID)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        return DB::table('master_events as me')
                 ->leftJoin('events as e', 'e.master_event_id', 'me.id')
                 ->leftJoin('master_teams as mth', 'mth.id', 'me.master_team_home_id')
                 ->leftJoin('master_teams as mta', 'mta.id', 'me.master_team_away_id')
                 ->leftJoin('sports as s', 's.id', 'me.sport_id')
                 ->leftJoin('master_event_markets as mem', 'mem.master_event_id', 'me.id')
                 ->leftJoin('event_markets as em', function ($join) {
                     $join->on('em.master_event_market_id', '=', 'mem.id');
                     $join->on('em.event_id', '=', 'e.id');
                 })
                 ->leftJoin('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                 ->whereNull('me.deleted_at')
                 ->where('mem.is_main', false)
                 ->where('me.master_event_unique_id', $meUID)
                 ->where('e.missing_count', '<=', $maxMissingCount)
                 ->select([
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
                     'e.event_identifier',
                     'e.master_event_id',
                     'em.market_event_identifier',
                     'em.master_event_market_id',
                     'em.event_id'
                 ])
                 ->distinct()->get();
    }

    public static function searchSuggestion(string $key)
    {
        return DB::table('search_suggestions')
                 ->where('label', 'ILIKE', '%' . trim($key) . '%');
    }
}
