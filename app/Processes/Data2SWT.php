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
            'MasterEvents'
        ];
        foreach ($swooleProcesses as $process) {
            $method = "db2Swt" . $process;
            self::{$method}($swoole);
        }

        $server = $swoole;
        $table = $server->eventsTable;
        foreach ($table as $key => $row) {
            var_dump($key);
            var_dump($row);
        }

        self::$quit = true;
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }

    private static function db2SwtProviders(Server $swoole)
    {
        $providers = DB::connection(config('database.crm_default'))->table('providers')->get();
        $providersTable = $swoole->providersTable;
        array_map(function ($provider) use ($providersTable) {
            $providersTable->set('provider:' . strtolower($provider->alias),
                ['id' => $provider->id, 'alias' => $provider->alias]);
        }, $providers->toArray());
    }

    private static function db2SwtMasterLeagues(Server $swoole)
    {
        /** TODO: table source will be changed */
        $leagues = DB::table('master_leagues')
            ->join('master_league_links', 'master_leagues.id', 'master_league_links.master_league_id')
            ->join('leagues', 'leagues.id', 'master_league_links.master_league_id')
            ->join('providers', 'providers.id', 'leagues.provider_id')
            /** TODO: additional query statement for GROUP BY to select `match_count` per league */
            ->select('master_leagues.id', 'master_leagues.sport_id', 'multi_league', 'providers.alias',
                'leagues.provider_id', 'leagues.league', 'master_leagues.updated_at')
            ->get();
        $leaguesTable = $swoole->leaguesTable;
        array_map(function ($league) use ($leaguesTable) {
            $leaguesTable->set('sportId:' . $league->sport_id . ':provider:' . strtolower($league->alias) . ':league:' . Str::slug($league->league),
                [
                    'id'           => $league->id,
                    'sport_id'     => $league->sport_id,
                    'provider_id'  => $league->provider_id,
                    'multi_league' => $league->multi_league,
                    'updated_at'   => strtotime($league->updated_at),
                    'match_count'  => 1,
                ]
            );
        }, $leagues->toArray());
    }

    private static function db2SwtLeagues(Server $swoole)
    {
        $leagues = DB::table('leagues')
            ->join('providers', 'providers.id', 'leagues.provider_id')
            ->join('sports', 'sports.id', 'leagues.sport_id')
            ->select('providers.alias', 'leagues.league', 'leagues.sport_id', 'leagues.provider_id', 'leagues.id')
            ->get();
        $leaguesTable = $swoole->rawLeaguesTable;
        array_map(function ($league) use ($leaguesTable) {
            $leaguesTable->set('sportId:' . $league->sport_id . ':provider:' . strtolower($league->alias) . 'league:' . Str::slug($league->league),
                [
                    'id'          => $league->id,
                    'provider_id' => $league->provider_id,
                    'sport_id'    => $league->sport_id,
                    'league'      => $league->league
                ]);
        }, $leagues->toArray());
    }

    private static function db2SwtSports(Server $swoole)
    {
        $sports = Sport::getActiveSports()->get();
        $sportsTable = $swoole->sportsTable;
        array_map(function ($sport) use ($sportsTable) {
            $sportsTable->set('sportId:' . $sport['id'], ['sport' => $sport['sport']]);
        }, $sports->toArray());

        // Odd Types
        $oddTypes = DB::table('odd_types')->get();
        $oddTypesTable = $swoole->oddTypesTable;
        array_map(function ($oddType) use ($oddTypesTable) {
            $oddTypesTable->set('oddType:' . $oddType->type, ['id' => $oddType->id, 'type' => $oddType->type]);
        }, $oddTypes->toArray());
    }

    private static function db2SwtMasterTeams(Server $swoole)
    {
        $teams = DB::table('master_teams')
            ->join('master_team_links', 'master_team_links.master_team_id', 'master_teams.id')
            ->join('teams', 'teams.id', 'master_team_links.team_id')
            ->join('providers', 'providers.id', 'teams.provider_id')
            ->select('providers.alias', 'teams.team', 'master_teams.multi_team', 'master_teams.id')
            ->get();
        $teamsTable = $swoole->teamsTable;
        array_map(function ($team) use ($teamsTable) {
            $teamsTable->set('provider:' . strtolower($team->alias) . 'team:' . Str::slug($team->team),
                ['id' => $team->id, 'multi_team' => $team->multi_team]);
        }, $teams->toArray());
    }

    private static function db2SwtTeams(Server $swoole)
    {
        $rawTeams = DB::table('teams')
            ->join('providers', 'providers.id', 'teams.provider_id')
            ->select('teams.id', 'teams.team', 'providers.alias', 'teams.provider_id')
            ->get();
        $rawTeamsTable = $swoole->rawTeamsTable;
        array_map(function ($team) use ($rawTeamsTable) {
            $rawTeamsTable->set('provider:' . Str::slug($team->alias) . ':team:' . Str::slug($team->team),
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
            $sportOddTypesTable->set('sportId:' . Str::slug($sportOddType->sport_id) . ':odd_type:' . Str::slug($sportOddType->type),
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
            ->join('providers', 'providers.id', 'events.provider_id')
            ->join('sports', 'sports.id', 'events.sport_id')
            ->join('leagues', 'leagues.id', 'events.league_id')
            ->select('events.id', 'events.event_identifier', 'events.provider_id', 'providers.alias',
                'events.league_id', 'events.sport_id', 'events.reference_schedule', 'events.team_home_id',
                'events.team_away_id', 'leagues.league')
            ->get();
        $rawEventsTable = $swoole->rawEventsTable;
        array_map(function ($event) use ($rawEventsTable) {
            $rawEventsTable->set('provider:' . strtolower($event->alias) . ':league:' . Str::slug($event->league) . ':eventIdentifier:' . $event->event_identifier,
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
            ->join('providers', 'providers.id', 'master_events.provider_id')
            ->join('sports', 'sports.id', 'master_events.sport_id')
            ->join('master_event_links', 'master_event_links.master_event_id', 'master_events.id')
            ->join('events', 'events.id', 'master_event_links.event_id')
            ->join('master_leagues', 'master_leagues.id', 'master_events.master_league_id')
            ->select('master_events.id', 'master_events.master_event_unique_id', 'master_events.provider_id',
                'providers.alias', 'events.event_identifier',
                'master_events.master_league_id', 'master_events.sport_id', 'master_events.reference_schedule',
                'master_events.master_team_home_id',
                'master_events.master_team_away_id', 'master_leagues.multi_league')
            ->get();
        $masterEventsTable = $swoole->eventsTable;
        array_map(function ($event) use ($masterEventsTable) {
            $masterEventsTable->set('provider:' . strtolower($event->alias) . ':eventIdentifier:' . $event->event_identifier,
                [
                    'id'                     => $event->id,
                    'master_league_id'       => $event->master_league_id,
                    'master_event_unique_id' => $event->master_event_unique_id,
                    'sport_id'               => $event->sport_id,
                    'master_team_home_id'    => $event->master_team_home_id,
                    'master_team_away_id'    => $event->master_team_away_id,
                    'provider_id'            => $event->provider_id,
                    'reference_schedule'     => $event->reference_schedule,
                    'multi_league'           => $event->multi_league
                ]);
        }, $masterEvents->toArray());
    }
}
