<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OrderTransactions extends Migration
{
    protected $tablename = "order_transactions";
    protected $integers = [
        'order_logs_id',
        'user_id',
        'source_id',
        'currency_id',
        'wallet_ledger_id',
        'provider_account_id',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tablename, function (Blueprint $table) {
            $table->bigIncrements('id');

            foreach ($this->integers AS $integer) {
                $table->integer($integer);
            }

            $table->text('reason');
            $table->double('amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tablename);
    }
}
