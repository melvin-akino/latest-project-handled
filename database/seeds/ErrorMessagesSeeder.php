<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ErrorMessagesSeeder extends Seeder
{
    protected $tablename = 'error_messages';
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
        	[
                'error'      => "Bet was not placed. Please try again.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'error'      => "The odds have changed. Please try again.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'error'      => "Internal Error. Please contact support.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'error'      => "Rejected.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'error'      => "Cancelled.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'error'      => "Abnormal bet.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'error'      => "Postponed.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'error'      => "Bookmaker cannot be reached.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'error'      => "Your bet is currently pending.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'error'      => "Bookmaker site is currently busy. Please try again later.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ];
        foreach ($data as $d) {
            DB::table($this->tablename)->insert($d);
        }
    }
}
