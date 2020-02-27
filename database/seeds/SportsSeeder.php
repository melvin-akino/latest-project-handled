<?php

use App\Models\Sport;

use Illuminate\Database\Seeder;

class SportsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sports = [
            [
                'id'         => 1,
                'sport'      => 'Soccer',
                'details'    => "Soccer Sports",
                'slug'       => 'soccer',
                'icon'       => 'sports_soccer',
                'is_enabled' => true
            ],
            [
                'id'         => 2,
                'sport'      => 'Basketball',
                'details'    => "Basketball Sports",
                'slug'       => 'basketball',
                'icon'       => 'sports_basketball',
                'is_enabled' => true
            ],
            [
                'id'         => 3,
                'sport'      => 'Tennis',
                'details'    => "Tennis Sports",
                'slug'       => 'tennis',
                'icon'       => 'sports_tennis',
                'is_enabled' => true
            ],
            [
                'id'         => 4,
                'sport'      => 'Baseball',
                'details'    => "Baseball Sports",
                'slug'       => 'baseball',
                'icon'       => 'sports_baseball',
                'is_enabled' => true
            ],

        ];

        for ($i = 0; $i < count($sports); $i++) {
            Sport::create([
                'id'         => $sports[$i]['id'],
                'sport'      => $sports[$i]['sport'],
                'details'    => $sports[$i]['details'],
                'priority'   => $i + 2,
                'slug'       => $sports[$i]['slug'],
                'icon'       => $sports[$i]['icon'],
                'is_enabled' => $sports[$i]['is_enabled'],
            ]);
        }
    }
}
