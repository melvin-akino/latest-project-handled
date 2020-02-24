<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablesOddTypesMasterLeaguesMasterTeams extends Migration
{
    protected $tables = [
        'odd_types',
        'master_leagues',
        'master_teams',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables AS $row) {
            Schema::table($row, function (Blueprint $table) {
                $table->softDeletes();
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
        foreach ($this->tables AS $row) {
            Schema::table($row, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
}
