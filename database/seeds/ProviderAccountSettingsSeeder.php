<?php

use Illuminate\Database\Seeder;
use App\Models\SystemConfiguration;

class ProviderAccountSettingsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $scheduleTimers = [
            'BET_NORMAL'        => ['value' => 3600,    'module' => 'ProviderAccount'],
            'BET_VIP'           => ['value' => 3600,    'module' => 'ProviderAccount'],
            'SCRAPER'           => ['value' => 1,       'module' => 'ProviderAccount'],
            'SCRAPER_MIN_MAX'   => ['value' => 1,       'module' => 'ProviderAccount'],
        ];

        foreach ($scheduleTimers as $key => $value) {
            SystemConfiguration::updateOrCreate([
                'type'  => $key,
                'value' => $value['value'],
                'module'=> $value['module']
            ], []);
        }
    }
}
