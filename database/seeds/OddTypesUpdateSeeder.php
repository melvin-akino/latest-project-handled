<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\OddType;

class OddTypesUpdateSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $oddTypes = [
            1  => '1X2',
            2  => 'HDP',
            3  => 'OU',
            4  => 'OE',
            5  => 'HT 1X2',
            6  => 'HT HDP',
            7  => 'HT OU',
            8  => 'HOME GOALS',
            9  => 'AWAY GOALS',
            10 => 'ML',
            11 => '1IML',
            12 => '1IHDP',
            13 => '1IOU',
            14 => 'HOME GAME',
            15 => 'AWAY GAME'
        ];

        foreach ($oddTypes as $key => $type) {
            OddType::updateOrCreate(
                [
                    'id' => $key,
                ],
                [
                    'type'       => $type,
                    'updated_at' => Carbon::now()
                ]
            );
        }
    }
}
