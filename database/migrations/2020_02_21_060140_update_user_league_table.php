<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserLeagueTable extends Migration
{
    protected $tablename = 'user_leagues';
    protected $newTableName = 'user_selected_leagues';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tablename)) {
            Schema::rename($this->tablename, $this->newTableName);
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
        }
    }
}
