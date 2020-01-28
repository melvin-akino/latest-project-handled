<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tablename = "countries";
        $data = config('db_' . $tablename);

        DB::table($tablename)
            ->insert($data);

        DB::table($tablename)
            ->update([
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
    }
}
