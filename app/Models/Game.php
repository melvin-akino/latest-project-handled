<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Log};

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

    public static function getGameDetails(int $masterLeagueId, string $schedule = 'early', int $userId, string $meUID = null)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        return DB::table('trade_window')
                 ->where('master_league_id', $masterLeagueId)
                 ->where('game_schedule', $schedule)
                 ->where('missing_count', '<=', $maxMissingCount)
                 ->where(function ($query) {
                    $query->where('is_main', true)
                        ->orWhereNull('is_main');
                })
                 ->when($meUID, function ($query, $meUID) {
                     return $query->where('master_event_unique_id', $meUID);
                 })
                 ->whereNotIn('master_event_id', function ($query) use ($userId) {
                     $query->select('master_event_id')->from('user_watchlist')->where('user_id', $userId);
                 })
                 ->select('*', DB::raw('CONCAT(type, market_flag, odd_label) as market_common'))
                 ->get();
    }

    public static function providersOfEvents(int $masterEventId, array $userProviderIds, string $schedule = null)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        return DB::table('master_events as me')
                 ->leftJoin('event_groups as eg', 'eg.master_event_id', 'me.id')
                 ->leftJoin('events as e', 'eg.event_id', 'e.id')
                 ->leftJoin('providers as p', 'p.id', 'e.provider_id')
                 ->where('eg.master_event_id', $masterEventId)
                 ->whereNull('me.deleted_at')
                 ->whereNull('e.deleted_at')
                 ->where('e.missing_count', '<=', $maxMissingCount)
                 ->where('p.is_enabled', true)
                 ->whereIn('p.id', $userProviderIds)
                 ->when($schedule, function ($query, $schedule) {
                    return $query->where('game_schedule', $schedule);
                 })
                 ->select('p.id', 'p.alias as provider')
                 ->distinct();
    }

    public static function getWatchlistGameDetails(int $userId, int $eventId = null)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        return DB::table('trade_window as tw')
                 ->leftJoin('user_watchlist as uw', 'uw.master_event_id', 'tw.master_event_id')
                 ->where('uw.user_id', $userId)
                 ->where('tw.missing_count', '<=', $maxMissingCount)
                 ->where(function ($query) {
                    $query->where('is_main', true)
                        ->orWhereNull('is_main');
                })
                 ->when($eventId, function ($query, $eventId) {
                     return $query->where('uw.master_event_id', $eventId);
                 })
                 ->select('*', DB::raw('CONCAT(type, market_flag, odd_label) as market_common'))
                 ->get();
    }

    public static function getOtherMarketSpreadDetails(array $fields = [])
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;
        // $primaryProvider = Provider::getIdFromAlias(SystemConfiguration::getSystemConfigurationValue('PRIMARY_PROVIDER')->value);

        return DB::table('master_events AS me')
                 ->leftJoin('event_groups AS eg', 'me.id', 'eg.master_event_id')
                 ->join('events as e', 'eg.event_id', 'e.id')
                 ->join('event_markets AS em', 'em.event_id', 'e.id')
                 ->leftJoin('odd_types AS ot', 'em.odd_type_id', 'ot.id')
                 ->whereNull('me.deleted_at')
                 ->whereNull('em.deleted_at')
                 ->whereNull('e.deleted_at')
                 // ->where('em.provider_id', $primaryProvider)
                 ->where('em.market_flag', $fields['market_flag'])
                 ->where('em.odd_type_id', $fields['odd_type_id'])
                 ->where('e.game_schedule', $fields['game_schedule'])
                 ->where('eg.master_event_id', $fields['master_event_id'])
                 ->where('e.missing_count', '<=', $maxMissingCount)
                 ->orderBy('em.odds', 'DESC')
                 ->get([
                     'em.mem_uid',
                     'em.odds',
                     'em.odd_label',
                     'em.is_main',
                     'em.provider_id'
                 ]);
    }

    public static function getMasterEventByMarketId(string $marketId, $providerId)
    {
        $primaryProvider = Provider::getIdFromAlias(SystemConfiguration::getSystemConfigurationValue('PRIMARY_PROVIDER')->value);
        $event           = DB::table('event_markets')->where('mem_uid', $marketId)->join('events', 'event_markets.event_id', 'events.id')->first();
        $masterEventId   = DB::table('event_groups')->select('master_event_id')->where('event_id', $event->event_id)->first();
        $eventIds        = DB::table('event_groups')->where('master_event_id', $masterEventId->master_event_id)->pluck('event_id');
        $query           = DB::table('master_events AS me')
            ->leftJoin('master_leagues as ml', 'ml.id', 'me.master_league_id')
            ->leftJoin('league_groups as lg', 'ml.id', 'lg.master_league_id')
            ->join('leagues as l', function ($join) use ($primaryProvider) {
                $join->on('l.id', 'lg.league_id');
                $join->where('l.provider_id', $primaryProvider);
            })
            ->leftJoin('master_teams as mth', 'mth.id', 'me.master_team_home_id')
            ->leftJoin('team_groups AS tgh', 'tgh.master_team_id', 'mth.id')
            ->join('teams AS th', function ($join) use ($primaryProvider) {
                $join->on('th.id', 'tgh.team_id');
                $join->where('th.provider_id', $primaryProvider);
            })
            ->leftJoin('master_teams as mta', 'mta.id', 'me.master_team_away_id')
            ->leftJoin('team_groups AS tga', 'tga.master_team_id', 'mta.id')
            ->join('teams AS ta', function ($join) use ($primaryProvider) {
                $join->on('ta.id', 'tga.team_id');
                $join->where('ta.provider_id', $primaryProvider);
            })
            ->leftJoin('event_groups as eg', 'me.id', 'eg.master_event_id')
            ->join('events as e', 'eg.event_id', 'e.id')
            ->join('event_markets AS em', 'em.event_id', 'e.id')
            ->leftJoin('odd_types AS ot', 'ot.id', 'em.odd_type_id')
            ->leftJoin('sport_odd_type as sot', function ($join) {
                $join->on('sot.odd_type_id', '=', 'ot.id');
                $join->on('sot.sport_id', '=', 'me.sport_id');
            })
            ->whereNull('me.deleted_at')
            ->whereNull('em.deleted_at')
            ->whereIn('em.event_id', $eventIds)
            ->where('em.market_flag', $event->market_flag)
            ->where('em.odd_type_id', $event->odd_type_id)
            ->where('em.odd_label', $event->odd_label)
            ->where('em.provider_id', $providerId)
            ->where('e.game_schedule', $event->game_schedule)
            ->select([
                'me.sport_id',
                'me.master_event_unique_id',
                DB::raw('COALESCE(ml.name, l.name) as master_league_name'),
                DB::raw('COALESCE(mth.name, th.name) as master_team_home_name'),
                DB::raw('COALESCE(mta.name, ta.name) as master_team_away_name'),
                'e.id AS event_id',
                'e.game_schedule',
                'e.running_time',
                'e.score',
                'em.mem_uid',
                'em.is_main',
                'em.market_flag',
                'em.odd_type_id',
                'em.bet_identifier',
                'em.provider_id',
                'em.odds',
                'em.odd_label',
                'sot.name AS column_type',
            ])
            ->first();

        return $query;
    }

    public static function getSelectedLeagueEvents(int $userId, int $sportId)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        return DB::table('trade_window as tw')
            ->leftJoin('user_selected_leagues as sl', function ($join) {
                $join->on('tw.master_league_id', '=', 'sl.master_league_id')
                    ->whereRaw('tw.game_schedule = sl.game_schedule');
            })
            ->where('sl.user_id', $userId)
            ->where('tw.missing_count', '<=', $maxMissingCount)
            ->where(function ($query) {
                $query->where('is_main', true)
                    ->orWhereNull('is_main');
            })
            ->whereNotIn('master_event_id', function ($query) use ($userId) {
                $query->select('master_event_id')->from('user_watchlist')->where('user_id', $userId);
            })
            ->where('tw.sport_id', $sportId)
            ->select('*', DB::raw('CONCAT(type, market_flag, odd_label) as market_common'))
            ->get();
    }

    public static function getWatchlistEvents(int $userId, int $sportId)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        return DB::table('trade_window as tw')
                 ->leftJoin('user_watchlist AS uw', 'tw.master_event_id', 'uw.master_event_id')
                 ->where('uw.user_id', $userId)
                 ->where('tw.missing_count', '<=', $maxMissingCount)
                 ->where('tw.sport_id', $sportId)
                 ->where(function ($query) {
                    $query->where('is_main', true)
                        ->orWhereNull('is_main');
                })
                ->select('*', DB::raw('CONCAT(type, market_flag, odd_label) as market_common'))
                 ->get();
    }

    public static function getOtherMarketsByMasterEventId(string $masterEventId)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;
        $primaryProvider = Provider::getIdFromAlias(SystemConfiguration::getSystemConfigurationValue('PRIMARY_PROVIDER')->value);

        $primaryOtherMarkets = DB::table('trade_window')
                ->where('master_event_unique_id', $masterEventId)
                ->where('missing_count', '<=', $maxMissingCount)
                ->where('is_main', false)
                ->where('provider_id', $primaryProvider)
                ->select('*', DB::raw('CONCAT(type, market_flag, odd_label) as market_common'));           

        $secondaryOtherMarkets = DB::table('trade_window')
                    ->where('master_event_unique_id', $masterEventId)
                    ->where('missing_count', '<=', $maxMissingCount)
                    ->where('provider_id', '!=', $primaryProvider)
                    ->select('*', DB::raw('CONCAT(type, market_flag, odd_label) as market_common'));

        return $secondaryOtherMarkets
                    ->union($primaryOtherMarkets)
                    ->get();
    }

    public static function getGameDetailsByMeId(int $masterEventId)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;

        return DB::table('master_leagues as ml')
                 ->leftJoin('sports as s', 's.id', 'ml.sport_id')
                 ->leftJoin('master_events as me', 'me.master_league_id', 'ml.id')
                 ->join('events as e', 'e.master_event_id', 'me.id')
                 ->leftJoin('master_teams as mth', 'mth.id', 'me.master_team_home_id')
                 ->leftJoin('master_teams as mta', 'mta.id', 'me.master_team_away_id')
                 ->leftJoin('master_event_markets as mem', 'me.id', 'mem.master_event_id')
                 ->leftJoin('odd_types as ot', 'ot.id', 'mem.odd_type_id')
                 ->join('event_markets as em', function ($join) {
                     $join->on('em.master_event_market_id', '=', 'mem.id');
                     $join->on('em.event_id', '=', 'e.id');
                     $join->where('em.is_main', true);
                 })
                 ->leftJoin('providers as p', 'p.id', 'em.provider_id')
                 ->select('ml.sport_id', 'ml.name as master_league_name', 'ml.id as league_id', 's.sport', 'e.master_event_id',
                     'me.master_event_unique_id', 'mth.name as master_team_home_name', 'mta.name as master_team_away_name',
                     'e.ref_schedule', 'e.game_schedule', 'e.score', 'e.running_time',
                     'e.home_penalty', 'e.away_penalty', 'em.odd_type_id', 'mem.master_event_market_unique_id', 'em.is_main', 'em.market_flag',
                     'ot.type', 'em.odds', 'em.odd_label', 'e.provider_id', 'em.bet_identifier', 'p.alias')
                 ->where('me.id', $masterEventId)
                 ->whereNull('me.deleted_at')
                 ->whereNull('e.deleted_at')
                 ->whereNull('em.deleted_at')
                 ->whereNull('ml.deleted_at')
                 ->where('e.missing_count', '<=', $maxMissingCount)
                 ->get();
    }

    public static function checkIfHasOtherMarkets(string $uid, array $userProviderIds)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;
        return DB::table('master_events as me')
                 ->join('event_groups as eg', 'me.id', 'eg.master_event_id')
                 ->join('events as e', 'eg.event_id', 'e.id')
                 ->join('event_markets as em', function($join) {
                    $join->on('e.id', 'em.event_id');
                    $join->where('em.is_main', false);
                })
                 ->where('me.master_event_unique_id', $uid)
                 ->whereIn('em.provider_id', $userProviderIds)
                 ->whereNull('e.deleted_at')
                 ->whereNull('em.deleted_at')
                 ->whereNull('me.deleted_at')
                 ->where('e.missing_count', '<=', $maxMissingCount)
                 ->exists();
    }

    public static function getAvailableEvents(int $userId, string $keyword)
    {
        $maxMissingCount = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT')->value;
        $primaryProvider = Provider::getIdFromAlias(SystemConfiguration::getSystemConfigurationValue('PRIMARY_PROVIDER')->value);

        return DB::table('trade_window')
            ->where('missing_count', '<=', $maxMissingCount)
            ->whereNotIn('master_event_id', function($query) use ($userId) {
                $query->select('master_event_id')->from('user_watchlist')->where('user_id', $userId);
            })
            ->where(function($query) use ($keyword) {
                $query->where(DB::raw("CONCAT(master_league_name, ' | ', master_team_home_name, ' VS ', master_team_away_name)"), 'ILIKE', str_replace('%', '^', $keyword) . '%')
                    ->orWhere('master_league_name', 'ILIKE', str_replace('%', '^', $keyword) . '%')
                    ->orWhere('master_team_home_name', 'ILIKE', str_replace('%', '^', $keyword) . '%')
                    ->orWhere('master_team_away_name', 'ILIKE', str_replace('%', '^', $keyword) . '%');
            })
            ->select([
                DB::raw("'event' as type"),
                'master_event_unique_id as data',
                DB::raw("CONCAT(master_league_name, ' | ', master_team_home_name, ' VS ', master_team_away_name) as label")
            ])
            ->groupBy('master_event_unique_id', 'master_league_name', 'master_team_home_name', 'master_team_away_name');
    }
}
