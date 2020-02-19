<?php

use Illuminate\Database\Seeder;

class SystemConfigurationSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $connection = config('database.crm_default', 'pgsql_crm');

        $scheduleTimers = [
            'SCHEDULE_INPLAY_TIMER'           => 1,
            'SCHEDULE_TODAY_TIMER'            => 5,
            'SCHEDULE_EARLY_TIMER'            => 10,
            'NUM_OF_REQ_PER_EXECUTION_INPLAY' => 5,
            'INTERVAL_REQ_PER_EXEC_INPLAY'    => 200,
            'NUM_OF_REQ_PER_EXECUTION_TODAY'  => 1,
            'INTERVAL_REQ_PER_EXEC_TODAY'     => 0,
            'NUM_OF_REQ_PER_EXECUTION_EARLY'  => 1,
            'INTERVAL_REQ_PER_EXEC_EARLY'     => 0
        ];

        foreach ($scheduleTimers as $key => $scheduleTimer) {
            DB::connection($connection)->table('system_configurations')->insert([
                'type'  => $key,
                'value' => $scheduleTimer,
            ]);
        }
    }
}
