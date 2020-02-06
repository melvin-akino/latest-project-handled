<?php

use Illuminate\Database\Seeder;
use App\Models\OddType;

class OddType1x1To1x2UpdateSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $oddTypeData = OddType::where('type', 'FT 1X1')->first();
        if ($oddTypeData) {
            $oddTypeData->type = 'FT 1X2';
            $oddTypeData->save();
        }
    }
}
