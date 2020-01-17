<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UsersAdditionalFields extends Migration
{
    protected $tablename = "users";

    public function up()
    {
        if (Schema::hasTable($this->tablename)) {
            Schema::table($this->tablename, function (Blueprint $table) {
                if (!Schema::hasColumn($this->tablename, 'status')) {
                    $table->smallInteger('status')
                        ->default(0)
                        ->comment('0 - inactive, 1 - active');
                }

                if (!Schema::hasColumn($this->tablename, 'status')) {
                    $table->smallInteger('is_reset')
                        ->default(1)
                        ->comment('0 - no change, 1 - user should reset');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable($this->tablename)) {
            Schema::table($this->tablename, function (Blueprint $table) {
                if (Schema::hasColumn($this->tablename, 'status')) {
                    $table->dropColumn('status');
                }

                if (Schema::hasColumn($this->tablename, 'is_reset')) {
                    $table->dropColumn('is_reset');
                }
            });
        }
    }
}
