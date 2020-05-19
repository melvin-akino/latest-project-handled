<?php

use Illuminate\Database\Seeder;
use \App\Models\SystemConfiguration;

class SystemConfigurationKafkaConfigSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $scheduleTimers = [
            'MINMAX_INPLAY_REQUEST_TIMER'           => 1,
            'MINMAX_TODAY_REQUEST_TIMER'            => 5,
            'MINMAX_EARLY_REQUEST_TIMER'            => 10,
            'OPEN_ORDERS_REQUEST_TIMER'             => 180,
            'SETTLEMENTS_REQUEST_TIMER'             => 180
        ];

        foreach ($scheduleTimers as $key => $scheduleTimer) {
            SystemConfiguration::firstOrCreate([
                'type'  => $key
            ], [
                'value' => $scheduleTimer,
            ]);
        }
    }
}
