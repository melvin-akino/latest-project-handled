<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\OddType;

class OddTypesAdditionalSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $oddTypes = [
            16 => '1SET ML',
            17 => '1SET HDP',
            18 => '1SET OU',
            19 => '1SET HOME GAME',
            20 => '1SET AWAY GAME',
            21 => '2SET ML',
            22 => '2SET HDP',
            23 => '2SET OU',
            24 => '2SET HOME GAME',
            25 => '2SET AWAY GAME',
            26 => '3SET ML',
            27 => '3SET HDP',
            28 => '3SET OU',
            29 => '3SET HOME GAME',
            30 => '3SET AWAY GAME',
            31 => '4SET ML',
            32 => '4SET HDP',
            33 => '4SET OU',
            34 => '4SET HOME GAME',
            35 => '4SET AWAY GAME'
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
