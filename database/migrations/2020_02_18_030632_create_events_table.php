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
                $table->integer('event_identifier');
                $table->integer('sport_id');
                $table->integer('provider_id');
                $table->string('league_name');
                $table->string('home_team_name');
                $table->string('away_team_name');
                $table->string('ref_schedule');
                $table->string('game_schedule');
                $table->softDeletes();
                $table->timestamps();

                $table->foreign('sport_id')
                    ->references('id')
                    ->on('sports')
                    ->onUpdate('cascade');

                $table->foreign('provider_id')
                    ->references('id')
                    ->on('providers')
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
