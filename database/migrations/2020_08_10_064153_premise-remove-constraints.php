<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PremiseRemoveConstraints extends Migration
{
    protected $me   = "master_events";
    protected $ev   = "events";
    protected $mem  = "master_event_markets";
    protected $em   = "event_markets";
    protected $meml = "master_event_market_logs";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->me, function (Blueprint $table) {
            $table->dropUnique([ 'master_event_unique_id' ]);
            $table->dropForeign([ 'master_league_id' ]);
            $table->dropForeign([ 'master_team_home_id' ]);
            $table->dropForeign([ 'master_team_away_id' ]);
            $table->dropForeign([ 'sport_id' ]);
        });

        Schema::table($this->ev, function (Blueprint $table) {
            $table->dropForeign([ 'league_id' ]);
            $table->dropForeign([ 'master_event_id' ]);
            $table->dropForeign([ 'provider_id' ]);
            $table->dropForeign([ 'sport_id' ]);
            $table->dropForeign([ 'team_home_id' ]);
            $table->dropForeign([ 'team_away_id' ]);
        });

        Schema::table($this->mem, function (Blueprint $table) {
            $table->dropUnique([ 'master_event_market_unique_id' ]);
            $table->dropForeign([ 'master_event_id' ]);
            $table->dropForeign([ 'odd_type_id' ]);
        });

        Schema::table($this->em, function (Blueprint $table) {
            $table->dropForeign([ 'event_id' ]);
            $table->dropForeign([ 'master_event_market_id' ]);
            $table->dropForeign([ 'odd_type_id' ]);
            $table->dropForeign([ 'provider_id' ]);
        });

        Schema::table($this->meml, function (Blueprint $table) {
            $table->dropForeign([ 'master_event_market_id' ]);
            $table->dropForeign([ 'odd_type_id' ]);
            $table->dropForeign([ 'provider_id' ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->me, function (Blueprint $table) {
            $table->unique('master_event_unique_id');

            $table->foreign('master_league_id')
                ->references('id')
                ->on('master_leagues')
                ->onUpdate('cascade');

            $table->foreign('master_team_home_id')
                ->references('id')
                ->on('master_teams')
                ->onUpdate('cascade');

            $table->foreign('master_team_away_id')
                ->references('id')
                ->on('master_teams')
                ->onUpdate('cascade');

            $table->foreign('sport_id')
                ->references('id')
                ->on('sports')
                ->onUpdate('cascade');
        });

        Schema::table($this->ev, function (Blueprint $table) {
            $table->foreign('league_id')
                ->references('id')
                ->on('leagues')
                ->onUpdate('cascade');

            $table->foreign('master_event_id')
                ->references('id')
                ->on('master_events')
                ->onUpdate('cascade');

            $table->foreign('provider_id')
                ->references('id')
                ->on('providers')
                ->onUpdate('cascade');

            $table->foreign('sport_id')
                ->references('id')
                ->on('sports')
                ->onUpdate('cascade');

            $table->foreign('team_home_id')
                ->references('id')
                ->on('teams')
                ->onUpdate('cascade');

            $table->foreign('team_away_id')
                ->references('id')
                ->on('teams')
                ->onUpdate('cascade');
        });

        Schema::table($this->mem, function (Blueprint $table) {
            $table->unique('master_event_market_unique_id');

            $table->foreign('master_event_id')
                ->references('id')
                ->on('master_events')
                ->onUpdate('cascade');

            $table->foreign('odd_type_id')
                ->references('id')
                ->on('odd_types')
                ->onUpdate('cascade');
        });

        Schema::table($this->em, function (Blueprint $table) {
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->onUpdate('cascade');

            $table->foreign('master_event_market_id')
                ->references('id')
                ->on('master_event_markets')
                ->onUpdate('cascade');

            $table->foreign('odd_type_id')
                ->references('id')
                ->on('odd_types')
                ->onUpdate('cascade');

            $table->foreign('provider_id')
                ->references('id')
                ->on('providers')
                ->onUpdate('cascade');
        });

        Schema::table($this->meml, function (Blueprint $table) {
            $table->foreign('master_event_market_id')
                ->references('id')
                ->on('master_event_markets')
                ->onUpdate('cascade');

            $table->foreign('odd_type_id')
                ->references('id')
                ->on('odd_types')
                ->onUpdate('cascade');

            $table->foreign('provider_id')
                ->references('id')
                ->on('providers')
                ->onUpdate('cascade');
        });
    }
}
