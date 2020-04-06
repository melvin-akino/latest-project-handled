<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableSystemConfigurationAddModuleField extends Migration
{
    protected $tablename = "system_configurations";
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table($this->tablename, function (Blueprint $table) {
            $table->text('module')->nullable();
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
            $table->dropColumn('module');
        });
    }
}
