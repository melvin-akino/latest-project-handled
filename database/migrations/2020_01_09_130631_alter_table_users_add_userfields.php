<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableUsersAddUserfields extends Migration
{
    protected $tablename = "users";
    protected $str = [
        'firstname' => 32,
        'lastname' => 32,
        'phone' => 32,
        'postcode' => 6,
    ];
    protected $text = [
        'address',
    ];
    protected $int = [
        'country_id',
        'state_id',
        'city_id',
        'phone_country_code',
        'currency_id',
    ];
    protected $date = [
        'birthdate',
    ];

    public function up()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            // STRINGS
            foreach ($this->str AS $column => $length) {
                $table->string($column, $length)->nullable();
            }

            // TEXTS
            foreach ($this->text AS $row) {
                $table->text($row)->nullable();
            }

            // INTEGERS
            foreach ($this->int AS $row) {
                $table->smallInteger($row)->nullable();
            }

            // DATES
            foreach ($this->date AS $row) {
                $table->date($row)->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            // STRINGS
            foreach ($this->str AS $column => $length) {
                $table->dropColumn($column);
            }

            // TEXTS
            foreach ($this->text AS $row) {
                $table->dropColumn($row);
            }

            // INTEGERS
            foreach ($this->int AS $row) {
                $table->dropColumn($row);
            }

            // DATES
            foreach ($this->date AS $row) {
                $table->dropColumn($row);
            }
        });
    }
}
