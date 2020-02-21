<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEventTeamTable extends Migration
{
    protected $tablename = 'event_teams';
    protected $newTableName = 'event_team_links';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tablename)) {
            Schema::rename($this->tablename, $this->newTableName);
            Schema::table($this->newTableName, function (Blueprint $table) {
                $table->dropColumn('id');
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
        if (Schema::hasTable($this->newTableName)) {
            Schema::rename($this->newTableName, $this->tablename);
            Schema::table($this->tablename, function (Blueprint $table) {
                $table->integerIncrements('id');
            });
        }
    }
}
