<?php

namespace App\Processes;

use App\Models\Sport;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Swoole\Http\Server;
use Swoole\Process;

class Data2SWT implements CustomProcessInterface
{
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        // Providers
        $swooleProcesses = [
            'Providers',
            'MasterLeagues',
            'Leagues',
            'Sports',
            'MasterTeams',
            'Teams',
            'SportOddTypes',
            'Events',
            'MasterEvents',
            'EventMarkets',
            'MasterEventMarkets'
        ];
        foreach ($swooleProcesses as $process) {
            $method = "db2Swt" . $process;
            self::{$method}($swoole);
        }

//        $table = app('swoole')->rawLeaguesTable;
//        foreach ($table as $key => $row) {
//            var_dump($key);
//            var_dump($row);
//        }
        while (!self::$quit) {}
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }

    private static function db2SwtSports(Server $swoole)
    {
        $sports = Sport::getActiveSports()->get();
        $sportsTable = $swoole->sportsTable;
        array_map(function ($sport) use ($sportsTable) {
            $sportsTable->set('sId:' . $sport['id'], ['sport' => $sport['sport'], 'id' => $sport['id']]);
        }, $sports->toArray());

        // Odd Types
        $oddTypes = DB::table('odd_types')->get();
        $oddTypesTable = $swoole->oddTypesTable;
        array_map(function ($oddType) use ($oddTypesTable) {
            $oddTypesTable->set('oddType:' . $oddType->type, ['id' => $oddType->id, 'type' => $oddType->type]);
        }, $oddTypes->toArray());
    }

    private static function db2SwtProviders(Server $swoole)
    {
        $providers = DB::connection(config('database.crm_default'))->table('providers')->get();
        $providersTable = $swoole->providersTable;
        array_map(function ($provider) use ($providersTable) {
            $providersTable->set('providerAlias:' . strtolower($provider->alias),
                ['id' => $provider->id, 'alias' => $provider->alias]);
        }, $providers->toArray());
    }

    private static function db2SwtLeagues(Server $swoole)
    {
        $leagues = DB::table('leagues')
            ->join('sports', 'sports.id', 'leagues.sport_id')
            ->select('leagues.league', 'leagues.sport_id', 'leagues.provider_id', 'leagues.id')
            ->get();
        $leaguesTable = $swoole->rawLeaguesTable;
        array_map(function ($league) use ($leaguesTable) {
            $leaguesTable->set('sId:' . $league->sport_id . ':pId:' . $league->provider_id . ':league:' . Str::slug($league->league),
                [
                    'id'          => $league->id,
                    'provider_id' => $league->provider_id,
                    'sport_id'    => $league->sport_id,
                    'league'      => $league->league
                ]);
        }, $leagues->toArray());
    }

    private static function db2SwtMasterLeagues(Server $swoole)
    {
        /** TODO: table source will be changed */
        $leagues = DB::table('master_leagues')
            ->join('master_league_links', 'master_leagues.id', 'master_league_links.master_league_id')
            ->join('leagues', 'leagues.id', 'master_league_links.master_league_id')
            /** TODO: additional query statement for GROUP BY to select `match_count` per league */
            ->select('master_leagues.id', 'master_leagues.sport_id', 'multi_league', 'master_league_links.league_id',
                'leagues.provider_id', 'leagues.league', 'master_leagues.updated_at')
            ->get();
        $leaguesTable = $swoole->leaguesTable;
        array_map(function ($league) use ($leaguesTable) {
            $leaguesTable->set('sId:' . $league->sport_id . ':pId:' . $league->provider_id . ':league:' . Str::slug($league->league),
                [
                    'id'           => $league->id,
                    'sport_id'     => $league->sport_id,
                    'provider_id'  => $league->provider_id,
                    'multi_league' => $league->multi_league,
                    'league_id'    => $league->league_id,
                    'updated_at'   => strtotime($league->updated_at),
                    'match_count'  => 1,
                ]
            );
        }, $leagues->toArray());
    }

    private static function db2SwtMasterTeams(Server $swoole)
    {
        $teams = DB::table('master_teams')
            ->join('master_team_links', 'master_team_links.master_team_id', 'master_teams.id')
            ->join('teams', 'teams.id', 'master_team_links.team_id')
            ->select('master_team_links.team_id', 'master_teams.multi_team', 'master_teams.id', 'teams.provider_id')
            ->get();
        $teamsTable = $swoole->teamsTable;
        array_map(function ($team) use ($teamsTable) {
            $teamsTable->set('pId:' . $team->provider_id . 'tId:' . $team->team_id,
                ['id' => $team->id, 'multi_team' => $team->multi_team, 'provider_id' => $team->provider_id]);
        }, $teams->toArray());
    }

    private static function db2SwtTeams(Server $swoole)
    {
        $rawTeams = DB::table('teams')
            ->select('teams.id', 'teams.team', 'teams.provider_id')
            ->get();
        $rawTeamsTable = $swoole->rawTeamsTable;
        array_map(function ($team) use ($rawTeamsTable) {
            $rawTeamsTable->set('pId:' . $team->provider_id . ':team:' . Str::slug($team->team),
                ['id' => $team->id, 'team' => $team->team, 'provider_id' => $team->provider_id]);
        }, $rawTeams->toArray());
    }

    private static function db2SwtSportOddTypes(Server $swoole)
    {
        $sportOddTypes = DB::table('sport_odd_type')
            ->join('odd_types', 'odd_types.id', 'sport_odd_type.odd_type_id')
            ->join('sports', 'sports.id', 'sport_odd_type.sport_id')
            ->select('sport_odd_type.sport_id', 'sport_odd_type.odd_type_id', 'odd_types.type', 'sport_odd_type.id')
            ->get();
        $sportOddTypesTable = $swoole->sportOddTypesTable;
        array_map(function ($sportOddType) use ($sportOddTypesTable) {
            $sportOddTypesTable->set('sId:' . Str::slug($sportOddType->sport_id) . ':oddType:' . Str::slug($sportOddType->type),
                [
                    'id'                => $sportOddType->id,
                    'sportId'           => $sportOddType->sport_id,
                    'sport_odd_type_id' => $sportOddType->id,
                    'type'              => $sportOddType->type
                ]);
        }, $sportOddTypes->toArray());
    }

    private static function db2SwtEvents(Server $swoole)
    {
        $events = DB::table('events')
            ->join('sports', 'sports.id', 'events.sport_id')
            ->join('leagues', 'leagues.id', 'events.league_id')
            ->select('events.id', 'events.event_identifier', 'events.provider_id',
                'events.league_id', 'events.sport_id', 'events.reference_schedule', 'events.team_home_id',
                'events.team_away_id', 'leagues.league')
            ->get();
        $rawEventsTable = $swoole->rawEventsTable;
        array_map(function ($event) use ($rawEventsTable) {
            $rawEventsTable->set('lId:' . $event->league_id . ':pId:' . $event->provider_id .  ':eventIdentifier:' . $event->event_identifier,
                [
                    'id'                 => $event->id,
                    'league_id'          => $event->league_id,
                    'event_identifier'   => $event->event_identifier,
                    'sport_id'           => $event->sport_id,
                    'team_home_id'       => $event->team_home_id,
                    'team_away_id'       => $event->team_away_id,
                    'provider_id'        => $event->provider_id,
                    'reference_schedule' => $event->reference_schedule
                ]);
        }, $events->toArray());
    }

    private static function db2SwtMasterEvents(Server $swoole)
    {
        $masterEvents = DB::table('master_events')
            ->join('sports', 'sports.id', 'master_events.sport_id')
            ->join('master_event_links', 'master_event_links.master_event_id', 'master_events.id')
            ->join('events', 'events.id', 'master_event_links.event_id')
            ->join('master_leagues', 'master_leagues.id', 'master_events.master_league_id')
            ->select('master_events.id', 'master_events.master_event_unique_id', 'master_events.provider_id',
                'events.event_identifier', 'master_events.master_league_id', 'master_events.sport_id',
                'master_events.reference_schedule', 'master_events.master_team_home_id',
                'master_events.master_team_away_id', 'master_leagues.multi_league')
            ->get();
        $masterEventsTable = $swoole->eventsTable;
        array_map(function ($event) use ($masterEventsTable) {
            $masterEventsTable->set('sId:' . $event->sport_id . ':pId:' . $event->provider_id . ':eId:' . $event->id,
                [
                    'id'                     => $event->id,
                    'master_league_id'       => $event->master_league_id,
                    'master_event_unique_id' => $event->master_event_unique_id,
                    'sport_id'               => $event->sport_id,
                    'master_team_home_id'    => $event->master_team_home_id,
                    'master_team_away_id'    => $event->master_team_away_id,
                    'provider_id'            => $event->provider_id,
                    'reference_schedule'     => $event->reference_schedule,
                    'multi_league'           => $event->multi_league,
                    'event_identifier'       => $event->event_identifier
                ]);
        }, $masterEvents->toArray());
    }

    private static function db2SwtEventMarkets(Server $swoole)
    {
        $eventMarkets = DB::table('event_markets')
            ->join('events', 'events.id', 'event_markets.event_id')
            ->join('odd_types', 'odd_types.id', 'event_markets.odd_type_id')
            ->select('event_markets.id', 'event_markets.event_id', 'event_markets.odd_type_id',
                'events.provider_id', 'events.league_id', 'event_markets.odds',
                'event_markets.odd_label', 'event_markets.bet_identifier', 'event_markets.is_main',
                'event_markets.market_flag', 'events.event_identifier')
            ->get();
        $eventMarketsTable = $swoole->rawEventMarketsTable;
        array_map(function ($eventMarket) use ($eventMarketsTable) {
            $eventMarketsTable->set('lId:' . $eventMarket->league_id . ':pId:' . $eventMarket->provider_id . ':eId:' . $eventMarket->event_id,
                [
                    'id'             => $eventMarket->id,
                    'league_id'      => $eventMarket->league_id,
                    'event_id'       => $eventMarket->event_id,
                    'odd_type_id'    => $eventMarket->odd_type_id,
                    'provider_id'    => $eventMarket->provider_id,
                    'odds'           => $eventMarket->odds,
                    'odd_label'      => $eventMarket->odd_label,
                    'bet_identifier' => $eventMarket->bet_identifier,
                    'is_main'        => $eventMarket->is_main,
                    'market_flag'    => $eventMarket->market_flag,
                ]);
        }, $eventMarkets->toArray());
    }

    private static function db2SwtMasterEventMarkets(Server $swoole)
    {
        $masterEventMarkets = DB::table('master_event_markets')
            ->join('master_event_market_links', 'master_event_market_links.master_event_market_id',
                'master_event_markets.id')
            ->join('master_events', 'master_events.master_event_unique_id',
                'master_event_markets.master_event_unique_id')
            ->join('event_markets', 'event_markets.id', 'master_event_market_links.event_market_id')
            ->join('events', 'events.id', 'event_markets.event_id')
            ->join('odd_types', 'odd_types.id', 'event_markets.odd_type_id')
            ->select('event_markets.id', 'master_event_markets.master_event_unique_id',
                'master_event_markets.master_event_market_unique_id',
                'master_event_market_links.event_market_id',
                'event_markets.event_id', 'event_markets.odd_type_id', 'events.provider_id',
                'event_markets.odds', 'event_markets.odd_label', 'event_markets.bet_identifier',
                'event_markets.is_main', 'event_markets.market_flag', 'events.event_identifier')
            ->get();
        $masterEventMarketsTable = $swoole->eventMarketsTable;
        array_map(function ($eventMarket) use ($masterEventMarketsTable) {
            $masterEventMarketsTable->set(
                'pId:' . $eventMarket->provider_id .
                ':meUniqueId:' . $eventMarket->master_event_unique_id .
                ':memUniqueId:' . $eventMarket->master_event_market_unique_id,
                [
                    'id'                            => $eventMarket->id,
                    'master_event_unique_id'        => $eventMarket->master_event_unique_id,
                    'event_market_id'               => $eventMarket->event_market_id,
                    'master_event_market_unique_id' => $eventMarket->master_event_market_unique_id,
                    'event_id'                      => $eventMarket->event_id,
                    'odd_type_id'                   => $eventMarket->odd_type_id,
                    'provider_id'                   => $eventMarket->provider_id,
                    'odds'                          => $eventMarket->odds,
                    'odd_label'                     => $eventMarket->odd_label,
                    'bet_identifier'                => $eventMarket->bet_identifier,
                    'is_main'                       => $eventMarket->is_main,
                    'market_flag'                   => $eventMarket->market_flag,
                ]);
        }, $masterEventMarkets->toArray());
    }
}
