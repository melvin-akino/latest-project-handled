<?php

namespace App\Console\Commands;

use App\Models\{
    MasterLeague,
    League,
    MasterTeam,
    Team,
    Provider
};

use Illuminate\Console\Command;

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
        $leagueName        = "";
        $team1             = "";
        $team2             = "";

        $this->info('Processing Matched Masterlist...');

        foreach ($json AS $row) {
            $this->line('Gathering League and Team names...');

            $providerId = Provider::getIdFromAlias($row->provider);
            $sportId    = $row->sport;

            if ($leagueName == "") {
                $leagueName = $row->league;
            }

            if ($team1 == "") {
                $team1 = $row->home;
            }

            if ($team2 == "") {
                $team2 = $row->away;
            }

            $masterLeague = MasterLeague::withTrashed()->updateOrCreate([
                'master_league_name' => $row->league,
                'sport_id'           => $sportId
            ], [
                'deleted_at' => null
            ]);

            $matchedLeagueId = $masterLeague->id;

            League::withTrashed()->updateOrCreate([
                'master_league_id' => $matchedLeagueId,
                'sport_id'         => $sportId,
                'provider_id'      => $providerId,
                'league_name'      => $leagueName
            ], [
                'deleted_at' => null
            ]);

            $masterTeam1 = MasterTeam::withTrashed()->updateOrCreate([
                'sport_id'         => $sportId,
                'master_team_name' => $row->home,
            ], [
                'deleted_at' => null
            ]);

            $matchedHomeTeamId = $masterTeam1->id;

            Team::withTrashed()->updateOrCreate([
                'sport_id'       => $sportId,
                'team_name'      => $team1,
                'provider_id'    => $providerId,
                'master_team_id' => $matchedHomeTeamId
            ], [
                'deleted_at' => null
            ]);

            $masterTeam2 = MasterTeam::withTrashed()->updateOrCreate([
                'sport_id'         => $sportId,
                'master_team_name' => $row->away,
            ], [
                'deleted_at' => null
            ]);

            $matchedAwayTeamId = $masterTeam2->id;

            Team::withTrashed()->updateOrCreate([
                'sport_id'       => $sportId,
                'team_name'      => $team2,
                'provider_id'    => $providerId,
                'master_team_id' => $matchedAwayTeamId
            ], [
                'deleted_at' => null
            ]);
        }

        $this->info('Matched Masterlist Encode Successful!');
    }
}
