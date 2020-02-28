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
            'Sports',
            'Providers',
            'MasterLeagues',
            'MasterTeams',
            'SportOddTypes',
            'MasterEvents',
            'MasterEventMarkets',
            'UserWatchlist'
        ];
        foreach ($swooleProcesses as $process) {
            $method = "db2Swt" . $process;
            self::{$method}($swoole);
        }

        $table = app('swoole')->wsTable;
         foreach ($table as $key => $row) {
             var_dump($key);
             var_dump($row);
         }
        while (!self::$quit) {
        }
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
                ['id' => $provider->id, 'alias' => $provider->alias, 'priority' => $provider->priority]);
        }, $providers->toArray());
    }

    private static function db2SwtMasterLeagues(Server $swoole)
    {
        /** TODO: table source will be changed */
        $leagues = DB::table('master_leagues')
            ->join('master_league_links', 'master_leagues.id', 'master_league_links.master_league_id')
//            ->where(DB::raw('LENGTH(master_leagues.deleted_at)'), '<>', 0)
            ->select('master_leagues.id', 'master_leagues.sport_id', 'master_leagues.master_league_name',
                'master_league_links.league_name',
                'master_league_links.provider_id', 'master_leagues.updated_at')
            ->get();
        $leaguesTable = $swoole->leaguesTable;
        array_map(function ($league) use ($leaguesTable) {
            $leaguesTable->set('sId:' . $league->sport_id . ':pId:' . $league->provider_id . ':league:' . Str::slug($league->league_name),
                [
                    'id'                 => $league->id,
                    'sport_id'           => $league->sport_id,
                    'provider_id'        => $league->provider_id,
                    'master_league_name' => $league->master_league_name,
                    'league_name'        => $league->league_name,
                ]
            );
        }, $leagues->toArray());
    }

    private static function db2SwtMasterTeams(Server $swoole)
    {
        $teams = DB::table('master_teams')
            ->join('master_team_links', 'master_team_links.master_team_id', 'master_teams.id')
            ->select('master_teams.id', 'master_team_links.team_name', 'master_teams.master_team_name',
                'master_team_links.provider_id')
            ->get();
        $teamsTable = $swoole->teamsTable;
        array_map(function ($team) use ($teamsTable) {
            $teamsTable->set('pId:' . $team->provider_id . ':teamName:' . Str::slug($team->team_name),
                [
                    'id'               => $team->id,
                    'team_name'        => $team->team_name,
                    'master_team_name' => $team->master_team_name,
                    'provider_id'      => $team->provider_id
                ]);
        }, $teams->toArray());
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
            $sportOddTypesTable->set('sId:' . $sportOddType->sport_id . ':oddType:' . Str::slug($sportOddType->type),
                [
                    'id'                => $sportOddType->id,
                    'sportId'           => $sportOddType->sport_id,
                    'sport_odd_type_id' => $sportOddType->id,
                    'type'              => $sportOddType->type
                ]);
        }, $sportOddTypes->toArray());
    }

    private static function db2SwtMasterEvents(Server $swoole)
    {
        $masterEvents = DB::table('master_events')
            ->join('sports', 'sports.id', 'master_events.sport_id')
            ->join('master_event_links', 'master_event_links.master_event_id', 'master_events.id')
            ->join('events', 'events.id', 'master_event_links.event_id')
            ->join('master_leagues', 'master_leagues.master_league_name', 'master_events.master_league_name')
            ->select('master_events.id', 'master_events.master_event_unique_id', 'events.provider_id',
                'events.event_identifier', 'master_leagues.id as master_league_id', 'master_events.sport_id',
                'master_events.ref_schedule', 'master_events.master_home_team_name',
                'master_events.master_away_team_name', 'master_leagues.master_league_name')
            ->get();
        $masterEventsTable = $swoole->eventsTable;
        array_map(function ($event) use ($masterEventsTable) {
            $masterEventsTable->set('sId:' . $event->sport_id . ':masterLeagueId:' . $event->master_league_id . ':eId:' . $event->id,
                [
                    'id'                     => $event->id,
                    'event_identifier'       => $event->event_identifier,
                    'sport_id'               => $event->sport_id,
                    'provider_id'            => $event->provider_id,
                    'master_league_id'       => $event->master_league_id,
                    'master_event_unique_id' => $event->master_event_unique_id,
                    'master_home_team_name'  => $event->master_home_team_name,
                    'master_away_team_name'  => $event->master_away_team_name,
                    'ref_schedule'           => $event->ref_schedule,
                    'master_league_name'     => $event->master_league_name,
                ]);
        }, $masterEvents->toArray());
    }

    private static function db2SwtMasterEventMarkets(Server $swoole)
    {
        $masterEventMarkets = DB::table('master_event_markets')
            ->join('master_event_market_links', 'master_event_market_links.master_event_market_id',
                'master_event_markets.id')
            ->join('event_markets', 'event_markets.id', 'master_event_market_links.event_market_id')
            ->join('master_events', 'master_events.master_event_unique_id',
                'master_event_markets.master_event_unique_id')
            ->join('odd_types', 'odd_types.id', 'master_event_markets.odd_type_id')
            ->select('event_markets.id', 'master_event_markets.master_event_unique_id',
                'master_event_markets.master_event_market_unique_id',
                'master_event_market_links.event_market_id',
                'event_markets.odd_type_id', 'event_markets.provider_id',
                'event_markets.odds', 'event_markets.odd_label', 'event_markets.bet_identifier',
                'event_markets.is_main', 'event_markets.market_flag')
            ->get();
        $masterEventMarketsTable = $swoole->eventMarketsTable;
        array_map(function ($eventMarket) use ($masterEventMarketsTable) {
            $masterEventMarketsTable->set(
                'pId:' . $eventMarket->provider_id .
                ':meUID:' . $eventMarket->master_event_unique_id .
                ':memUID:' . $eventMarket->master_event_market_unique_id,
                [
                    'id'                            => $eventMarket->id,
                    'master_event_unique_id'        => $eventMarket->master_event_unique_id,
                    // 'event_market_id'               => $eventMarket->event_market_id,
                    'master_event_market_unique_id' => $eventMarket->master_event_market_unique_id,
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

    private static function db2SwtUserWatchlist(Server $swoole)
    {
        $userWatchlist = DB::table('user_watchlist')
            ->get();
        $wsTable = $swoole->wsTable;
        array_map(function ($watchlist) use ($wsTable) {
            $wsTable->set(
                'userWatchlist:' . $watchlist->user_id .
                ':masterEventUniqueId:' . $watchlist->master_event_unique_id,
                ['value' => $watchlist->id]);
        }, $userWatchlist->toArray());
    }
}
