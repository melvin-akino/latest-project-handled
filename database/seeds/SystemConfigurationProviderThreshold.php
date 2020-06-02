<?php

use Illuminate\Database\Seeder;
use App\Models\SystemConfiguration;

class SystemConfigurationProviderThreshold extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        $thresholds = [
            'BET_NORMAL_THRESHOLD'              => 4500,
            'BET_VIP_THRESHOLD'                 => 4500,
            'PROVIDER_THRESHOLD_SEND_EMAIL_TO'  => 'janroxas@ninepinetech.com'

        ];
        foreach ($thresholds as $key => $value) {
            SystemConfiguration::updateOrCreate([
                'type'  => $key,
            ], [
                'value' => $value,
                
            ]);

        }

    }
}
