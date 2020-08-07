<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, Artisan};

class MemColumnUpdate extends Migration
{
    protected $masterEventMarketLogsTable = 'master_event_market_logs';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->masterEventMarketLogsTable)) {
            Schema::table($this->masterEventMarketLogsTable, function (Blueprint $table) {
                $table->string('master_event_market_unique_id', 100)->nullable();
            });

            Artisan::call('db:seed', [
                '--class' => MemLogsMissingDataSeeder::class
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable($this->masterEventMarketLogsTable)) {
            Schema::table($this->masterEventMarketLogsTable, function (Blueprint $table) {
                $table->dropColumn('master_event_market_unique_id');
            });
        }
    }
}
