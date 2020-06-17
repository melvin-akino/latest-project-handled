<?php

use Illuminate\Database\Seeder;
use App\Models\SystemConfiguration;

class MissingCountSystemConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $missingMaxCounts = [
            'INPLAY_MISSING_MAX_COUNT_FOR_DELETION' => 30,
            'TODAY_MISSING_MAX_COUNT_FOR_DELETION'  => 10,
            'EARLY_MISSING_MAX_COUNT_FOR_DELETION'  => 5,
            'EVENT_VALID_MAX_MISSING_COUNT'         => 3
        ];

        foreach($missingMaxCounts as $key => $value) {
            SystemConfiguration::firstOrCreate([
                'type'  => $key,
                'value' => $value
            ]);
        }
    }
}
