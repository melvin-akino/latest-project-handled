<?php

namespace App\Console\Commands;

use App\Models\{MasterLeague, MasterLeagueLink, MasterTeam, MasterTeamLink};
use Illuminate\Console\Command;

class MasterlistCommand extends Command
{
    protected $signature = 'masterlist:create {league} {team_1} {team_2} {sport_id} {provider_id}';

    protected $description = 'Creating Masterlist';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $leagueName = $this->argument('league');
        $team1 = $this->argument('team_1');
        $team2 = $this->argument('team_2');
        $sportId = $this->argument('sport_id');
        $providerId = $this->argument('provider_id');

        $masterLeague = MasterLeague::withTrashed()->updateOrCreate([
            'master_league_name' => $leagueName,
            'sport_id'           => $sportId
        ], [
            'deleted_at' => null
        ]);

        MasterLeagueLink::withTrashed()->updateOrCreate([
            'master_league_id' => $masterLeague->id,
            'sport_id'         => $sportId,
            'provider_id'      => $providerId,
            'league_name'      => $leagueName
        ], [
            'deleted_at' => null
        ]);

        $team1 = MasterTeam::withTrashed()->updateOrCreate([
            'sport_id'         => $sportId,
            'master_team_name' => $team1,
        ], [
            'deleted_at' => null
        ]);

        MasterTeamLink::withTrashed()->updateOrCreate([
            'sport_id'       => $sportId,
            'team_name'      => $team1,
            'provider_id'    => $providerId,
            'master_team_id' => $team1->id
        ], [
            'deleted_at' => null
        ]);

        $team2 = MasterTeam::withTrashed()->updateOrCreate([
            'sport_id'         => $sportId,
            'master_team_name' => $team2,
        ], [
            'deleted_at' => null
        ]);

        MasterTeamLink::withTrashed()->updateOrCreate([
            'sport_id'       => $sportId,
            'team_name'      => $team2,
            'provider_id'    => $providerId,
            'master_team_id' => $team2->id
        ], [
            'deleted_at' => null
        ]);
    }
}
