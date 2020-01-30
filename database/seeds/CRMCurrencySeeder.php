<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CRMCurrencySeeder extends Seeder
{
    protected $connection;
    protected $tablename = "currency";

    public function __construct()
    {
        $this->connection = config('database.crm_default', 'pgsql_crm');
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currency = [
            [
                'id'         => 1,
                'name'       => "Chinese Yuan",
                'code'       => "CNY",
                'symbol'     => "¥",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'id'         => 2,
                'name'       => "US Dollar",
                'code'       => "USD",
                'symbol'     => "$",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ];

        foreach ($currency AS $row) {
            DB::connection($this->connection)->table($this->tablename)->insert($row);
        }
    }
}
