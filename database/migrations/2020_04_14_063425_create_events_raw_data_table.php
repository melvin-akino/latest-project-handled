<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsRawDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events_raw_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('sport_id');
            $table->integer('provider_id');
            $table->string('league_name', 100);
            $table->string('home_team_name', 100);
            $table->string('away_team_name', 100);
            $table->string('ref_schedule');
            $table->string('game_schedule');
            $table->string('event_identifier');
            $table->text('raw');
            $table->boolean('is_matched')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events_raw_data');
    }
}
