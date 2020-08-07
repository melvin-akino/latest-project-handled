<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\EventScore;

class EventScoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $meUIDs = DB::table('orders')->distinct()->pluck('master_event_unique_id')->toArray();
        EventScore::fillDataFromOrders($meUIDs);
    }
}
