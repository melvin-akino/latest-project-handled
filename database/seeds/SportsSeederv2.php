<?php

use App\Models\Sport;

use Illuminate\Database\Seeder;

class SportsSeederv2 extends Seeder
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
                'sport'      => 'Basketball',
                'details'    => "Basketball Sports",
                'is_enabled' => false
            ],
            [
                'sport'      => 'American Football',
                'details'    => "American Football Sports",
                'is_enabled' => false
            ],
            [
                'sport'      => 'Baseball',
                'details'    => "Baseball Sports",
                'is_enabled' => false
            ],
            [
                'sport'      => 'E Sports',
                'details'    => "Electronic Games Sports",
                'is_enabled' => false
            ],
        ];

        for ($i = 0; $i < count($sports); $i++) {
            Sport::create([
                'sport'      => $sports[$i]['sport'],
                'details'    => $sports[$i]['details'],
                'priority'   => $i + 2,
                'is_enabled' => $sports[$i]['is_enabled'],
            ]);
        }
    }
}
