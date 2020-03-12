<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeRateTable extends Migration
{
    protected $tableName = 'exchange_rates';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->integer('from_currency_id');
                $table->integer('to_currency_id');
                $table->float('default_amount')->default(1);
                $table->float('exchange_rate');
                $table->timestamps();

                $table->foreign('from_currency_id')
                    ->references('id')
                    ->on('currency')
                    ->onUpdate('cascade');

                $table->foreign('to_currency_id')
                    ->references('id')
                    ->on('currency')
                    ->onUpdate('cascade');

                $table->unique(['from_currency_id', 'to_currency_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}
