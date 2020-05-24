<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScheduleUserSelectedLeaguesTable extends Migration
{
    protected $tableName = 'user_selected_leagues';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('game_schedule', 10)->index()->default('early');
                $table->integer('sport_id')->index()->default(1);
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
        if (Schema::hasTable($this->tableName)) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('game_schedule');
                $table->dropColumn('sport_id');
            });
        }
    }
}
