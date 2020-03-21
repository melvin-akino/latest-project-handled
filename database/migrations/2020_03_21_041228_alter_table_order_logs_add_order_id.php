<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableOrderLogsAddOrderId extends Migration
{
    protected $tablename = "order_logs";
    protected $column    = "order_id";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->tablename, function (Blueprint $table) {
            $table->integer($this->column)->nullable();

            $table->foreign($this->column)
                ->references('id')
                ->on('orders')
                ->onUpdate('cascade');
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
            $table->dropForeign([ $this->column ]);
        });

        Schema::table($this->tablename, function (Blueprint $table) {
            $table->dropColumn($this->column);
        });
    }
}
