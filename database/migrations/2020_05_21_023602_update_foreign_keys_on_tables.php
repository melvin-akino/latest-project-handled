<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForeignKeysOnTables extends Migration
{
    
    protected $userSelectedLeaguesTable = 'user_selected_leagues';
    protected $userWatchlistTable       = 'user_watchlist';
    protected $masterLeaguesTable       = 'master_leagues';
    protected $masterEventsTable        = 'master_events';

    public function up()
    {
        if (Schema::hasTable($this->userSelectedLeaguesTable)) {
            Schema::table($this->userSelectedLeaguesTable, function (Blueprint $table) {
                $table->integer('master_league_id')->nullable()->index();
                $table->index('game_schedule');
                $table->foreign('master_league_id')
                    ->references('id')
                    ->on($this->masterLeaguesTable)
                    ->onUpdate('cascade');
            });
        }

        if (Schema::hasTable($this->userWatchlistTable)) {
            Schema::table($this->userWatchlistTable, function (Blueprint $table) {
                $table->integer('master_event_id')->nullable()->index();
                $table->foreign('master_event_id')
                    ->references('id')
                    ->on($this->masterEventsTable)
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
        if (Schema::hasTable($this->userSelectedLeaguesTable)) {
            Schema::table($this->userSelectedLeaguesTable, function (Blueprint $table) {
                $table->dropColumn('master_league_id');
                $table->dropIndex($this->userSelectedLeaguesTable . '_game_schedule_index');
            });
        }

        if (Schema::hasTable($this->userWatchlistTable)) {
            Schema::table($this->userWatchlistTable, function (Blueprint $table) {
                $table->dropColumn('master_event_id');
            });
        }
    }
}
