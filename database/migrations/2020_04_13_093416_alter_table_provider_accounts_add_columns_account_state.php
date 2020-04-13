<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableProviderAccountsAddColumnsAccountState extends Migration
{
    protected $tablename = "provider_accounts";
    protected $booleans  = [
        'is_idle',
        'is_enabled',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            foreach ($this->booleans AS $boolean) {
                $table->boolean($boolean)->default(true);
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
            foreach ($this->booleans AS $boolean) {
                $table->dropColumn($boolean);
            }
        });
    }
}
