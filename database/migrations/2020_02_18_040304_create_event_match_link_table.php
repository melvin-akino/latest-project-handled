<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventMatchLinkTable extends Migration
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
                $table->integer('sport_id');
                $table->string('master_event_unique_id');
                $table->string('master_league_name')->index();
                $table->string('master_home_team_name')->index();
                $table->string('master_away_team_name')->index();
                $table->string('game_schedule', 10)->default('early');
                $table->softDeletes();
                $table->timestamps();

                $table->foreign('sport_id')
                    ->references('id')
                    ->on('sports')
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
