<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableSportsAddColumnsSlugIcon extends Migration
{
    protected $tablename = "sports";
    protected $strings = [
        'slug' => 'soccer',
        'icon' => 'fa-futbol',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            foreach ($this->strings AS $string => $default) {
                $table->string($string, 30)->default($default)->nullable();
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
            foreach ($this->strings AS $string => $default) {
                $table->dropColumn($string);
            }
        });
    }
}
