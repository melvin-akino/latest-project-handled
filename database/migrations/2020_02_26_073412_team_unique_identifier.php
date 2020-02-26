<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TeamUniqueIdentifier extends Migration
{
    protected $tablename = 'teams';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tablename)) {
            Schema::table($this->tablename, function (Blueprint $table) {
                $table->dropUnique(['team']);
                $table->unique(['team', 'provider_id']);
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
                $table->dropUnique(['team', 'provider_id']);
                $table->unique('team');
            });
        }
    }
}
