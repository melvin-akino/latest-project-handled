<?php

use Illuminate\Database\Seeder;
use App\Models\Provider as ProviderModel;

class ProvidersSeeder extends Seeder
{
    protected $tablename = 'providers';
    protected $connection;

    public function __construct()
    {
        $this->connection = config('database.crm_default', 'pgsql_crm');
    }

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $providers = [
            1 => [
                'alias' => 'HG',
                'name'  => "Singbet"
            ],
            2 => [
                'alias' => 'PIN',
                'name'  => "Pinnacle"
            ],
            3 => [
                'alias' => 'ISN',
                'name'  => "ISN88"
            ]
        ];

        foreach ($providers as $key => $provider) {
            DB::connection($this->connection)->table($this->tablename)->insert([
                'name'              => $provider['name'],
                'alias'             => $provider['alias'],
                'punter_percentage' => 45,
                'priority'          => $key,
                'is_enabled'        => true
            ]);
        }
    }
}
