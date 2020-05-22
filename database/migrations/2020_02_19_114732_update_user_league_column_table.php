<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserLeagueColumnTable extends Migration
{
    protected $tablename = 'user_leagues';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tablename)) {
            Schema::table($this->tablename, function (Blueprint $table) {
                $table->integer('master_league_id')->nullable();
                $table->integer('user_id')->nullable();
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onUpdate('cascade');
                $table->unique('master_league_id');

            });

            Schema::table($this->tablename, function (Blueprint $table) {
                $table->dropColumn('uid');
            });

            Schema::table($this->tablename, function (Blueprint $table) {
                $table->dropColumn('flag');
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
        if (Schema::hasTable($this->tablename)) {
            Schema::table($this->tablename, function (Blueprint $table) {
                $table->string('uid', 100)->nullable();
                $table->unique('uid');
                $table->tinyInteger('flag')
                    ->comment('0 - for deletion, 1 - selected, 2 - watchlist')
                    ->nullable();
            });

            Schema::table($this->tablename, function (Blueprint $table) {
                $table->dropColumn('master_league_id');
            });

            Schema::table($this->tablename, function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
}
