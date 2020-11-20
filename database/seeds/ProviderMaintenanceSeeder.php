<?php

use Illuminate\Database\Seeder;
use \App\Models\SystemConfiguration;

class ProviderMaintenanceSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $providerMaintenance = [
            'HG_MAINTENANCE'  => 0,
            'ISN_MAINTENANCE' => 0,
            'PIN_MAINTENANCE' => 0
        ];

        foreach ($providerMaintenance as $key => $value) {
            SystemConfiguration::firstOrCreate([
                'type' => $key
            ], [
                'value' => $value,
                'module' => 'ProviderMaintenance'
            ]);
        }
    }
}
