<?php

namespace App\Console\Commands;

use App\Models\{MasterLeague, League, MasterTeam, Team};
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
        $team1      = $this->argument('team_1');
        $team2      = $this->argument('team_2');
        $sportId    = $this->argument('sport_id');
        $providerId = $this->argument('provider_id');

        $masterLeague = MasterLeague::withTrashed()->updateOrCreate([
            'name'       => $leagueName,
            'sport_id'   => $sportId
        ], [
            'deleted_at' => null
        ]);

        League::withTrashed()->updateOrCreate([
            'master_league_id' => $masterLeague->id,
            'sport_id'         => $sportId,
            'provider_id'      => $providerId,
            'name'             => $leagueName
        ], [
            'deleted_at' => null
        ]);

        $masterTeam1 = MasterTeam::withTrashed()->updateOrCreate([
            'sport_id' => $sportId,
            'name'     => $team1,
        ], [
            'deleted_at' => null
        ]);

        Team::withTrashed()->updateOrCreate([
            'sport_id'       => $sportId,
            'name'           => $team1,
            'provider_id'    => $providerId,
            'master_team_id' => $masterTeam1->id
        ], [
            'deleted_at' => null
        ]);

        $masterTeam2 = MasterTeam::withTrashed()->updateOrCreate([
            'sport_id' => $sportId,
            'name'     => $team2,
        ], [
            'deleted_at' => null
        ]);

        Team::withTrashed()->updateOrCreate([
            'sport_id'       => $sportId,
            'name'           => $team2,
            'provider_id'    => $providerId,
            'master_team_id' => $masterTeam2->id
        ], [
            'deleted_at' => null
        ]);
    }
}
