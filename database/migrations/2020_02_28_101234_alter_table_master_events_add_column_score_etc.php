<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableMasterEventsAddColumnScoreEtc extends Migration
{
    protected $tablename = "master_events";
    protected $strings   = [
        'score',
        'running_time',
    ];
    protected $integers  = [
        'home_penalty',
        'away_penalty',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            foreach ($this->strings AS $string) {
                $table->string($string)->nullable();
            }

            foreach ($this->integers AS $integer) {
                $table->integer($integer)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            foreach ($this->strings AS $string) {
                $table->dropColumn($string);
            }

            foreach ($this->integers AS $integer) {
                $table->dropColumn($integer);
            }
        });
    }
}
