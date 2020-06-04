<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatabaseAdjustmentsMV721 extends Migration
{
    protected $pao = "provider_account_orders";
    protected $or  = "orders";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->pao, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('order_log_id');
            $table->integer('exchange_rate_id');
            $table->float('actual_stake', 10, 2);
            $table->float('actual_to_win', 10, 2);
            $table->float('actual_profit_loss', 10, 2);
            $table->float('exchange_rate');
            $table->timestamps();

            $table->foreign('exchange_rate_id')
                ->references('id')
                ->on('exchange_rates')
                ->onUpdate('cascade');

            $table->foreign('order_log_id')
                ->references('id')
                ->on('order_logs')
                ->onUpdate('cascade');
        });

        Schema::table($this->or, function (Blueprint $table) {
            $table->dropColumn('actual_profit_loss');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->pao);

        Schema::table($this->or, function (Blueprint $table) {
            $table->float('actual_profit_loss', 10, 2)->nullable();
        });
    }
}
