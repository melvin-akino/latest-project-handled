<?php

use Illuminate\Database\Seeder;
use App\Models\Provider as ProviderModel;

class ProvidersSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $providers = [
            'PIN' => "Pinnacle",
            'HG'  => "Singbet",
            "ISN" => "ISN88"
        ];

        foreach ($providers as $alias => $name) {
            ProviderModel::create([
                'name'              => $name,
                'alias'             => $alias,
                'punter_percentage' => 45,
                'priority'          => 1,
                'is_enabled'        => true
            ]);
        }
    }
}
