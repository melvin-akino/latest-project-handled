<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTeamsAndLeaguesForeigns extends Migration
{
    protected $teamsTable   = 'teams';
    protected $leaguesTable = 'leagues';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->teamsTable)) {
            Schema::table($this->teamsTable, function (Blueprint $table) {
                $table->integer('master_team_id')->nullable()->change();

            });
        }

        if (Schema::hasTable($this->leaguesTable)) {
            Schema::table($this->leaguesTable, function (Blueprint $table) {
                $table->integer('master_league_id')->nullable()->change();

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
        // No turning back
    }
}
