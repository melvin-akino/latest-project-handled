<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableUsersAddForeignKeys extends Migration
{
    protected $tablename = "users";
    protected $fk = [
        'country_id',
        'state_id',
        'city_id'
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            foreach ($this->fk AS $fk) {
                $table->foreign($fk)
                    ->references('id')
                    ->on('countries')
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
                $table->dropForeign($this->tablename . '_' . $fk . '_foreign');
            }
        });
    }
}
