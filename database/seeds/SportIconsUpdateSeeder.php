<?php

use Illuminate\Database\Seeder;
use App\Models\Sport;

class SportIconsUpdateSeeder extends Seeder
{
    private const SOCCER = 1;
    private const BASKETBALL = 2;
    private const AMERICAN_FOOTBALL = 3;
    private const BASEBALL = 4;
    private const TENNIS = 5;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Sport::updateOrCreate([
            'id' => self::TENNIS
        ], [
            'sport'   => 'Tennis',
            'details' => 'Tennis Sports'
        ]);
        $icons = [
            self::SOCCER            => 'sports_soccer',
            self::BASKETBALL        => 'sports_basketball',
            self::AMERICAN_FOOTBALL => 'sports_football',
            self::BASEBALL          => 'sports_baseball',
            self::TENNIS            => 'sports_tennis',
        ];

        foreach ($icons as $sportId => $icon) {
            Sport::updateOrCreate([
                'id' => $sportId
            ], [
                'icon' => $icon,
            ]);
        }
    }
}
