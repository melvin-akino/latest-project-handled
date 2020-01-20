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
            'Soccer'
        ];

        foreach ($sports as $key => $sport) {
            $sportModel = new SportModel();
            $sportModel->sport = $sport;
            $sportModel->details = 'Ball Sports';
            $sportModel->priority = 1;
            $sportModel->is_enabled = true;
            $sportModel->created_at = Carbon::now();
            $sportModel->updated_at = Carbon::now();
            $sportModel->save();
        }
    }
}
