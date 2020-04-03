<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCrmtransferAddLedger extends Migration
{
    protected $tablename = "crmtransfer";
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table($this->tablename, function (Blueprint $table) {
            $table->integer('wallet_ledger_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
         Schema::table($this->tablename, function (Blueprint $table) {
            $table->dropColumn('wallet_ledger_id');
        });

    }
}
