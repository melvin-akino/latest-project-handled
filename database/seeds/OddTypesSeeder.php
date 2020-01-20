<?php

use Illuminate\Database\Seeder;
use App\Models\OddType;
use Carbon\Carbon;

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
            'FT 1X1',
            'FT Handicap',
            'FT O/U',
            'FT O/E',
            '1H 1X2',
            '1H Handicap',
            '1H O/U'
        ];

        foreach ($oddTypes as $key => $type) {
            $oddType = new OddType();
            $oddType->type = $type;
            $oddType->created_at = Carbon::now();
            $oddType->updated_at = Carbon::now();
            $oddType->save();
        }
    }
}
