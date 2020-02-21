<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEventTable extends Migration
{
    protected $tablename = 'events';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tablename)) {
            Schema::table($this->tablename, function (Blueprint $table) {
                $table->renameColumn('event_id', 'event_identifier');
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
                $table->renameColumn('event_identifier', 'event_id');
            });
        }
    }
}
