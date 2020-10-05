<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MemLogsMissingDataSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        return;
        $sql = "UPDATE master_event_market_logs SET
                master_event_market_unique_id = master_event_markets.master_event_market_unique_id
            FROM master_event_markets WHERE master_event_market_logs.master_event_market_id = master_event_markets.id";

        DB::update($sql);
    }
}

