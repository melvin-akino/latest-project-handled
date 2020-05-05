<?php

namespace App\Console\Commands;

use App\Models\{
    MasterLeague,
    MasterLeagueLink,
    MasterTeam,
    MasterTeamLink,
    Provider
};

use Illuminate\Console\Command;

// [{"provider":"hg","sport":1,"league":"League","home":"Home","away":"Away"},{"provider":"isn","sport":1,"league":"League","home":"Home","away":"Away"}]

class MatchedMasterlistCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matched:create {json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creating Matched Masterlist';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $json              = json_decode($this->argument('json'));
        $matchedLeagueId   = 0;
        $matchedHomeTeamId = 0;
        $matchedAwayTeamId = 0;

        foreach ($json AS $row) {
            $providerId = Provider::getIdFromAlias($row->provider);
            $sportId    = $row->sport;
            $leagueName = $row->league;
            $team1      = $row->home;
            $team2      = $row->away;

            $masterLeague = MasterLeague::withTrashed()->updateOrCreate([
                'master_league_name' => $leagueName,
                'sport_id'           => $sportId
            ], [
                'deleted_at' => null
            ]);

            if ($matchedLeagueId == 0) {
                $matchedLeagueId = $masterLeague->id;
            }

            MasterLeagueLink::withTrashed()->updateOrCreate([
                'master_league_id' => $matchedLeagueId,
                'sport_id'         => $sportId,
                'provider_id'      => $providerId,
                'league_name'      => $leagueName
            ], [
                'deleted_at' => null
            ]);

            $masterTeam1 = MasterTeam::withTrashed()->updateOrCreate([
                'sport_id'         => $sportId,
                'master_team_name' => $team1,
            ], [
                'deleted_at' => null
            ]);

            if ($matchedHomeTeamId == 0) {
                $matchedHomeTeamId = $masterTeam1->id;
            }

            MasterTeamLink::withTrashed()->updateOrCreate([
                'sport_id'       => $sportId,
                'team_name'      => $team1,
                'provider_id'    => $providerId,
                'master_team_id' => $matchedHomeTeamId
            ], [
                'deleted_at' => null
            ]);

            $masterTeam2 = MasterTeam::withTrashed()->updateOrCreate([
                'sport_id'         => $sportId,
                'master_team_name' => $team2,
            ], [
                'deleted_at' => null
            ]);

            if ($matchedAwayTeamId == 0) {
                $matchedAwayTeamId = $masterTeam2->id;
            }

            MasterTeamLink::withTrashed()->updateOrCreate([
                'sport_id'       => $sportId,
                'team_name'      => $team2,
                'provider_id'    => $providerId,
                'master_team_id' => $matchedAwayTeamId
            ], [
                'deleted_at' => null
            ]);
        }
    }
}
