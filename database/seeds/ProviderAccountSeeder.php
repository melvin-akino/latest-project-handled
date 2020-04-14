<?php

use App\Models\CRM\ProviderAccount;
use Illuminate\Database\Seeder;

class ProviderAccountSeeder extends Seeder
{
    protected $data = [
        [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "ml2079",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 100000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ],
        [
            'provider_id'       => 1,
            'type'              => "BET_VIP",
            'username'          => "ml2078",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 50000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ],
        [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "ml2077",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 1000000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->data AS $row) {
            ProviderAccount::create($row);
        }
    }
}
