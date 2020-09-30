<?php

use App\Models\ErrorMessage;
use Illuminate\Database\Seeder;

class ErrorMessagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
        	[
                'id'         => 1,
                'error'       => "Bet was not placed. Please try again.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id'         => 2,
                'error'       => "The odds have changed. Please try again.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id'         => 3,
                'error'       => "Internal Error. Please contact support.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id'         => 4,
                'error'       => "Rejected.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id'         => 5,
                'error'       => "Cancelled.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id'         => 6,
                'error'       => "Abnormal bet.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id'         => 7,
                'error'       => "Postponed.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id'         => 8,
                'error'       => "Bookmaker cannot be reached.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id'         => 9,
                'error'       => "Your bet is currently pending.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id'         => 10,
                'error'       => "Bookmaker site is currently busy. Please try again later.",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ];
        ErrorMessage::create($data);
    }
}
