<?php

use App\Models\CRM\ProviderAccount;
use Illuminate\Database\Seeder;

class ProviderAccountSeeder extends Seeder
{
    protected $data = [
        [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "chca005NP",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 100000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ], [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "chca007NP",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 50000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ], [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "chca016NP",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 1000000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ], [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "uat001",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 1000000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ], [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "NPTdev2",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 1000000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ], [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "NPTdev3",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 1000000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ], [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "NPTdev4",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 1000000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ], [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "NPTdev5",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 1000000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ], [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "NPTdev6",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 1000000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ], [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "ioagent10",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 1000000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ], [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "ioagent13",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 1000000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ], [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "ioagent14",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 1000000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ], [
            'provider_id'       => 1,
            'type'              => "BET_NORMAL",
            'username'          => "ioagent15",
            'password'          => "pass8888",
            'punter_percentage' => 45,
            'credits'           => 1000000,
            'is_idle'           => true,
            'is_enabled'        => true,
            'deleted_at'        => null,
        ]
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->data AS $row) {
            ProviderAccount::firstOrCreate(['username' => $row['username']], $row);
        }
    }
}
