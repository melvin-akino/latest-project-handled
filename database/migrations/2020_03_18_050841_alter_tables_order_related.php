<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablesOrderRelated extends Migration
{
    protected $tables = [
        'orders',
        'order_logs',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables AS $row) {
            Schema::table($row, function (Blueprint $table) use ($row) {
                $table->text('reason')->nullable();
                $table->float('profit_loss', 10, 2)->nullable();
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
        foreach ($this->tables AS $row) {
            Schema::table($row, function (Blueprint $table) use ($row) {
                $table->dropColumn('reason');
                $table->dropColumn('profit_loss');
            });
        }
    }
}
