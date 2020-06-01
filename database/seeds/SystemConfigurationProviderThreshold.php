<?php

use Illuminate\Database\Seeder;

class SystemConfigurationProviderThreshold extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $thresholds = [
        	'BET_NORMAL_THRESHOLD' 			   => 4500,
        	'BET_VIP_THRESHOLD'				   => 4500,
        	'PROVIDER_THRESHOLD_SEND_EMAIL_TO' => 'janroxas@ninepinetech.com'

        ];
        foreach ($thresholds as $key => $value) {
            DB::table('system_configurations')->insert([
                'type'  => $key,
                'value' => $value,
            ]);
        }

    }
}
