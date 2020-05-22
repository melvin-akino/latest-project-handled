<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablesMV689V2 extends Migration
{
    protected $ev    = "events";
    protected $me    = "master_events";
    protected $em    = "event_markets";
    protected $mem   = "master_event_markets";
    protected $meml  = "master_event_market_logs";
    protected $memln = "master_event_market_links";
    protected $ml    = "master_leagues";
    protected $mll   = "master_league_links";
    protected $mt    = "master_teams";
    protected $mtl   = "master_team_links";
    protected $or    = "orders";
    protected $us    = "user_selected_leagues";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // EVENTS :: START
            Schema::table($this->ev, function (Blueprint $table) {
                $table->dropColumn([
                    'league_name',
                    'home_team_name',
                    'away_team_name',
                ]);

                $table->integer('league_id')->index()->nullable();
                $table->integer('home_team_id')->index()->nullable();
                $table->integer('away_team_id')->index()->nullable();

                $table->string('game_schedule', 10)->change();
                $table->string('ref_schedule', 30)->change();

                $table->foreign('league_id')
                    ->references('id')
                    ->on('master_leagues')
                    ->onUpdate('cascade');

                $table->foreign('home_team_id')
                    ->references('id')
                    ->on('master_teams')
                    ->onUpdate('cascade');

                $table->foreign('away_team_id')
                    ->references('id')
                    ->on('master_teams')
                    ->onUpdate('cascade');
            });
        // EVENTS :: END

        // MASTER EVENTS :: START
            Schema::table($this->me, function (Blueprint $table) {
                $table->integer('master_home_team_id')->index()->nullable();
                $table->integer('master_away_team_id')->index()->nullable();

                $table->string('game_schedule', 10)->change();
                $table->string('ref_schedule', 30)->change();

                $table->foreign('master_home_team_id')
                    ->references('id')
                    ->on('master_teams')
                    ->onUpdate('cascade');

                $table->foreign('master_away_team_id')
                    ->references('id')
                    ->on('master_teams')
                    ->onUpdate('cascade');
            });
        // MASTER EVENTS :: END

        // EVENT MARKETS :: START
            Schema::table($this->em, function (Blueprint $table) {
                $table->dropColumn([
                    'master_event_unique_id',
                    'game_schedule',
                ]);

                $table->string('market_flag', 10)->change();
            });
        // EVENT MARKETS :: END

        // MASTER EVENT MARKETS :: START
            Schema::table($this->mem, function (Blueprint $table) {
                $table->dropColumn('master_event_unique_id');

                $table->string('market_flag', 10)->change();
            });
        // MASTER EVENT MARKETS :: END

        // MASTER EVENT MARKET LOGS :: START
            Schema::table($this->meml, function (Blueprint $table) {
                $table->string('market_flag', 10)->change();
            });
        // MASTER EVENT MARKET LOGS :: END

        // MASTER EVENT MARKET LINKS :: START
            Schema::table($this->meml, function (Blueprint $table) {
                $table->string('game_schedule', 10)->nullable();
            });
        // MASTER EVENT MARKET LINKS :: END

        // MASTER LEAGUES :: START
            Schema::table($this->ml, function (Blueprint $table) {
                $table->renameColumn('master_league_name', 'name');
            });
        // MASTER LEAGUES :: END

        // MASTER LEAGUE LINKS :: START
            Schema::table($this->mll, function (Blueprint $table) {
                $table->renameColumn('league_name', 'name');
            });
        // MASTER LEAGUE LINKS :: END

        // MASTER TEAMS :: START
            Schema::table($this->mt, function (Blueprint $table) {
                $table->renameColumn('master_team_name', 'name');
            });
        // MASTER TEAMS :: END

        // MASTER TEAM LINKS :: START
            Schema::table($this->mtl, function (Blueprint $table) {
                $table->renameColumn('team_name', 'name');
            });
        // MASTER TEAM LINKS :: END

        // ORDERS :: START
            Schema::table($this->or, function (Blueprint $table) {
                $table->dropColumn('settled_date');
            });

            Schema::table($this->or, function (Blueprint $table) {
                $table->dateTimeTz('settled_date')->index()->nullable();
            });
        // ORDERS :: END

        // USER SELECTED LEAGUES :: START
            Schema::table($this->us, function (Blueprint $table) {
                $table->string('game_schedule', 10)->change();
            });
        // USER SELECTED LEAGUES :: END
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // EVENTS :: START
            Schema::table($this->ev, function (Blueprint $table) {
                $table->dropForeign([ 'league_id' ]);
                $table->dropForeign([ 'home_team_id' ]);
                $table->dropForeign([ 'away_team_id' ]);
            });

            Schema::table($this->ev, function (Blueprint $table) {
                $table->dropColumn('league_id');
                $table->dropColumn('home_team_id');
                $table->dropColumn('away_team_id');

                $table->string('league_name', 100)->nullable();
                $table->string('home_team_name', 100)->nullable();
                $table->string('away_team_name', 100)->nullable();
            });
        // EVENTS :: END

        // MASTER EVENTS :: START
            Schema::table($this->me, function (Blueprint $table) {
                $table->dropForeign([ 'master_home_team_id' ]);
                $table->dropForeign([ 'master_away_team_id' ]);
            });

            Schema::table($this->me, function (Blueprint $table) {
                $table->dropColumn([
                    'master_home_team_id',
                    'master_away_team_id',
                ]);

                $table->string('master_league_name', 100)->nullable();
                $table->string('master_home_team_name', 100)->nullable();
                $table->string('master_away_team_name', 100)->nullable();
            });
        // MASTER EVENTS :: END

        // EVENT MARKETS :: START
            Schema::table($this->em, function (Blueprint $table) {
                $table->string('master_event_unique_id', 30)->nullable();
                $table->string('game_schedule', 10)->nullable();
            });
        // EVENT MARKETS :: END

        // MASTER EVENT MARKETS :: START
            Schema::table($this->mem, function (Blueprint $table) {
                $table->string('master_event_unique_id', 30)->nullable();
            });
        // MASTER EVENT MARKETS :: END

        // MASTER EVENT MARKET LINKS :: START
            Schema::table($this->meml, function (Blueprint $table) {
                $table->dropColumn('game_schedule');
            });
        // MASTER EVENT MARKET LINKS :: END

        // MASTER LEAGUES :: START
            Schema::table($this->ml, function (Blueprint $table) {
                $table->renameColumn('name', 'master_league_name');
            });
        // MASTER LEAGUES :: END

        // MASTER LEAGUE LINKS :: START
            Schema::table($this->mll, function (Blueprint $table) {
                $table->renameColumn('name', 'league_name');
            });
        // MASTER LEAGUE LINKS :: END

        // MASTER TEAMS :: START
            Schema::table($this->mt, function (Blueprint $table) {
                $table->renameColumn('name', 'master_team_name');
            });
        // MASTER TEAMS :: END

        // MASTER TEAM LINKS :: START
            Schema::table($this->mtl, function (Blueprint $table) {
                $table->renameColumn('name', 'team_name');
            });
        // MASTER TEAM LINKS :: END

        // ORDERS :: START
            Schema::table($this->or, function (Blueprint $table) {
                $table->dropColumn('settled_date')->index()->nullable();
            });

            Schema::table($this->or, function (Blueprint $table) {
                $table->string('settled_date', 100)->nullable();
            });
        // ORDERS :: END
    }
}
