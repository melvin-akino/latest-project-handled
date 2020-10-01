<?php

use Illuminate\Database\Seeder;
use App\Models\SystemConfiguration;

class ProviderReservationPercentageSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        SystemConfiguration::firstOrCreate([
            'type' => 'PROVIDER_ACCOUNT_RESERVATION_PERCENTAGE'
        ], [
            'value' => 20,
        ]);
    }
}
