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
                $table->integer('user_id')->nullable();
                $table->text('reason')->nullable();
                $table->float('profit_loss')->nullable();
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onUpdate('cascade');
                $table->foreign('provider_id')
                    ->references('id')
                    ->on('providers')
                    ->onUpdate('cascade');
                // if ($row == "orders") {
                //     $table->foreign('market_id')
                //         ->references('master_event_market_unique_id')
                //         ->on('master_event_markets')
                //         ->onUpdate('cascade');
                // }
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
            Schema::table($row, function (Blueprint $table) {
                $table->dropColumn('user_id');
                $table->dropColumn('reason');
                $table->dropColumn('profit_loss');
            });
        }
    }
}