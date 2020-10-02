<?php

use Illuminate\Database\Seeder;
use App\Models\EventMarket;
use Illuminate\Support\Facades\DB;

class OrderMissingDataFromEventsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        
        $orderMarketIds = DB::table('orders')->distinct()->pluck('market_id')->toArray();
        EventMarket::getMarketDetailsByListOfMarketIds($orderMarketIds);
    }
}

