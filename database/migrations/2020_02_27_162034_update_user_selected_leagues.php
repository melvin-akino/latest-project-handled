<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserSelectedLeagues extends Migration
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
                $table->dropColumn('master_league_id');
                $table->string('master_league_name', 100)->default('');
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
                $table->dropColumn('master_league_name');
                $table->integer('master_league_id');
                $table->foreign('master_league_id')
                    ->references('id')
                    ->on('master_leagues')
                    ->onUpdate('cascade');
            });
        }
    }
}
