<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterEventsTable extends Migration
{
    protected $tablename = 'master_events';

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
                $table->string('master_event_unique_id', 30)->unique();
                $table->integer('sport_id')->index();
                $table->integer('master_league_id')->index();
                $table->integer('master_team_home_id')->index();
                $table->integer('master_team_away_id')->index();
                $table->string('ref_schedule', 30);
                $table->string('game_schedule', 10)->index()->default('early');
                $table->softDeletes();
                $table->timestamps();

                $table->foreign('sport_id')
                    ->references('id')
                    ->on('sports')
                    ->onUpdate('cascade');

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
