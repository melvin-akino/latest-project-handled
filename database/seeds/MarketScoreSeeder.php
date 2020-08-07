<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\MarketScore;

class MarketScoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $orderMarketIds = DB::table('orders')->distinct()->pluck('market_id')->toArray();
        MarketScore::fillDataFromOrders($orderMarketIds);
    }
}
