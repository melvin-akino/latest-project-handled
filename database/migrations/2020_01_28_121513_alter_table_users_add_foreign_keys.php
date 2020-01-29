<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableUsersAddForeignKeys extends Migration
{
    protected $tablename = "users";
    protected $fk = [
        'country_id' => 'countries',
        'state_id'   => 'states',
        'city_id'    => 'cities'
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            foreach ($this->fk AS $fkey => $rel) {
                $table->foreign($fkey)
                    ->references('id')
                    ->on($rel)
                    ->onUpdate('cascade');
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
            foreach ($this->fk AS $fk) {
                $table->dropForeign([$fk]);
            }
        });
    }
}
