<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitiesSeederBatch4 extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tablename = "cities";
        $data = config('db_' . $tablename . '_batch4');
        $rows = collect($data)->chunk(1000)->toArray();

        foreach ($rows AS $row) {
            DB::table($tablename)->insert($row);
        }

        DB::table($tablename)
            ->update([
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
    }
}
