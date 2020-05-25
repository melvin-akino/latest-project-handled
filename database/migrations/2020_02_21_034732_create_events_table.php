<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    protected $tablename = 'events';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tablename)) {
            Schema::create($this->tablename, function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->integer('master_event_id')->index();
                $table->integer('event_identifier')->index();
                $table->integer('sport_id')->index();
                $table->integer('provider_id')->index();
                $table->integer('league_id')->index();
                $table->integer('team_home_id')->index();
                $table->integer('team_away_id')->index();
                $table->string('ref_schedule', 30);
                $table->string('game_schedule', 10)->index()->default('early');
                $table->softDeletes();
                $table->timestamps();

                $table->foreign('master_event_id')
                    ->references('id')
                    ->on('master_events')
                    ->onUpdate('cascade');

                $table->foreign('sport_id')
                    ->references('id')
                    ->on('sports')
                    ->onUpdate('cascade');

                $table->foreign('provider_id')
                    ->references('id')
                    ->on('providers')
                    ->onUpdate('cascade');

                $table->foreign('league_id')
                    ->references('id')
                    ->on('master_league_links')
                    ->onUpdate('cascade');

                $table->foreign('team_home_id')
                    ->references('id')
                    ->on('master_team_links')
                    ->onUpdate('cascade');

                $table->foreign('team_away_id')
                    ->references('id')
                    ->on('master_team_links')
                    ->onUpdate('cascade');
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
        Schema::dropIfExists($this->tablename);
    }
}
