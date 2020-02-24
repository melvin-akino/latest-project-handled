<?php

namespace App\Jobs;

use App\Models\OddType;
use App\Models\Sport;
use App\Models\Teams;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Data2SWT
{
    use Dispatchable;

    public function handle()
    {
        // Providers
        $providers = DB::connection(config('database.crm_default'))->table('providers')->get();
        $providersTable = app('swoole')->providersTable;
        array_map(function ($provider) use ($providersTable) {
            $providersTable->set('provider:' . strtolower($provider->alias), ['id' => $provider->id, 'alias' => $provider->alias]);
        }, $providers->toArray());

        // Master Leagues
        /** TODO: table source will be changed */
        $leagues = DB::table('master_leagues')
            ->join('master_league_links', 'master_leagues.id', 'master_league_links.master_league_id')
            ->join('leagues', 'leagues.id', 'master_league_links.master_league_id')
            ->join('providers', 'providers.id', 'leagues.provider_id')
            /** TODO: additional query statement for GROUP BY to select `match_count` per league */
            ->select('master_leagues.id', 'master_leagues.sport_id', 'multi_league', 'providers.alias', 'leagues.provider_id', 'leagues.league', 'master_leagues.updated_at')
            ->get();
        $leaguesTable = app('swoole')->leaguesTable;
        array_map(function ($league) use ($leaguesTable) {
            $leaguesTable->set(
                implode(':', ['sportId:' . $league->sport_id, 'provider:' . strtolower($league->alias),  'league' . Str::slug($league->league)]),
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

        // Sports
        $sports = Sport::getActiveSports()->get();
        $sportsTable = app('swoole')->sportsTable;
        array_map(function ($sport) use ($sportsTable) {
            $sportsTable->set('sportId:' . $sport['id'], ['sport' => $sport['sport']]);
        }, $sports->toArray());

        // Odd Types
        $oddTypes = DB::table('odd_types')->get();
        $oddTypesTable = app('swoole')->oddTypesTable;
        array_map(function ($oddType) use ($oddTypesTable) {
            $oddTypesTable->set('oddType:' . $oddType->type, ['id' => $oddType->id, 'type' => $oddType->type]);

        }, $oddTypes->toArray());

        // Master Teams
        $teams = DB::table('master_teams')
            ->join('master_team_links', 'master_team_links.master_team_id', 'master_teams.id')
            ->join('teams', 'teams.id', 'master_team_links.team_id')
            ->join('providers', 'providers.id', 'teams.provider_id')
            ->select('providers.alias', 'teams.team', 'master_teams.multi_team', 'master_teams.id')
            ->get();
        $teamsTable = app('swoole')->teamsTable;
        array_map(function ($team) use ($teamsTable) {
            $teamsTable->set('provider:' . strtolower($team->alias) . 'team:' . Str::slug($team->team), ['id' => $team->id, 'multi_team' => $team->multi_team]);
        }, $teams->toArray());

        //Raw Teams
        $rawTeams = DB::table('teams')
            ->join('providers', 'providers.id', 'teams.provider_id')
            ->select('teams.id', 'teams.team', 'providers.alias', 'teams.provider_id')
            ->get();
        $rawTeamsTable = app('swoole')->rawTeamsTable;
        array_map(function ($team) use ($rawTeamsTable) {
            $rawTeamsTable->set('provider:' . Str::slug($team->alias). ':team:' . Str::slug($team->team), ['id' => $team->id, 'team' => $team->team, 'provider_id' => $team->provider_id]);
        }, $rawTeams->toArray());

        // Sport Odd Types
        $sportOddTypes = DB::table('sport_odd_type')
            ->join('odd_types', 'odd_types.id', 'sport_odd_type.odd_type_id')
            ->join('sports', 'sports.id', 'sport_odd_type.sport_id')
            ->select('sport_odd_type.sport_id', 'sport_odd_type.odd_type_id', 'odd_types.type', 'sport_odd_type.id')
            ->get();
        $sportOddTypesTable = app('swoole')->sportOddTypesTable;
        array_map(function ($sportOddType) use ($sportOddTypesTable) {
            $sportOddTypesTable->set('sportId:' . Str::slug($sportOddType->sport_id). ':odd_type:' . Str::slug($sportOddType->type), ['id' => $sportOddType->id, 'sportId' => $sportOddType->sport_id, 'sport_odd_type_id' => $sportOddType->id, 'type' => $sportOddType->type]);
        }, $sportOddTypes->toArray());

        $server = app('swoole');
        $table = $server->teamsTable;
        foreach ($table as $key => $row) {
            var_dump($row);
        }
    }
}
