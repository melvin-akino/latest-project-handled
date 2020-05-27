<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablesForConventions extends Migration
{
    protected $fromTeamTable    = 'master_team_links';
    protected $fromLeagueTable  = 'master_league_links';
    protected $toTeamTable      = 'teams';
    protected $toLeagueTable    = 'leagues';
    protected $eventsTable      = 'events';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->fromTeamTable)) {
            Schema::rename($this->fromTeamTable, $this->toTeamTable);
        }

        if (Schema::hasTable($this->fromLeagueTable)) {
            Schema::rename($this->fromLeagueTable, $this->toLeagueTable);
        }

        if (Schema::hasTable($this->eventsTable)) {
            Schema::table($this->eventsTable, function(Blueprint $table) {
                $table->dropColumn('game_schedule');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable($this->toTeamTable)) {
            Schema::rename($this->toTeamTable, $this->fromTeamTable);
        }

        if (Schema::hasTable($this->toLeagueTable)) {
            Schema::rename($this->toLeagueTable, $this->fromLeagueTable);
        }

        if (Schema::hasTable($this->eventsTable)) {
            Schema::table($this->eventsTable, function(Blueprint $table) {
                $table->string('game_schedule', 10)->index()->default('early');
            });
        }
    }
}
