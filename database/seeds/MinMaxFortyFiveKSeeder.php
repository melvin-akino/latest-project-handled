<?php

use Illuminate\Database\Seeder;
use App\Models\SystemConfiguration;

class MinMaxFortyFiveKSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SystemConfiguration::firstOrCreate([
            'type'  => 'MAX_BET',

        ], [
            'value' => 45000
        ]);
    }
}
