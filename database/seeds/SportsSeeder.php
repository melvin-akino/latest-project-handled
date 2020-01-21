<?php

use Illuminate\Database\Seeder;
use App\Models\Sport as SportModel;
use Carbon\Carbon;

class SportsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $sports = [
            'Soccer' => "Football Sports"
        ];

        foreach ($sports as $sport => $detail) {
            SportModel::create([
                'sport'      => $sport,
                'details'    => $detail,
                'priority'   => 1,
                'is_enabled' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
