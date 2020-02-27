<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\OddType;

class OddTypesSeeder extends Seeder
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
            2  => 'ML',
            3  => 'HDP',
            4  => 'OU',
            5  => 'OE',
            6  => 'HOME GOALS',
            7  => 'AWAY GOALS',
            8  => 'HOME GAME',
            9  => 'AWAY GAME',
            10 => 'HT 1X2',
            11 => 'HT HDP',
            12 => 'HT OU',
            13 => 'HT HOME GOALS',
            14 => 'HT AWAY GOALS',
            15 => '1IML',
            16 => '1IHDP',
            17 => '1IOU',
            18 => '1SET ML',
            19 => '1SET HDP',
            20 => '1SET OU',
            21 => '2SET ML',
            22 => '2SET HDP',
            23 => '2SET OU',
            24 => '3SET ML',
            25 => '3SET HDP',
            26 => '3SET OU',
            27 => '4SET ML',
            28 => '4SET HDP',
            29 => '4SET OU',
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
