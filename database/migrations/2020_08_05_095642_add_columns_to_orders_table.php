<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToOrdersTable extends Migration
{
    protected $ordersTable = 'orders';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->ordersTable)) {
            Schema::table($this->ordersTable, function (Blueprint $table) {
                $table->string('final_score', 10)->nullable();
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
        if (Schema::hasTable($this->ordersTable)) {
            Schema::table($this->ordersTable, function (Blueprint $table) {
                $table->dropColumn('final_score');
            });
        }
    }
}
