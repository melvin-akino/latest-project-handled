<?php

use App\User;
use App\Models\Source;
use App\Models\UserWallet;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class InternalUsers extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sourceId = Source::getIdByName('REGISTRATION');

        $data = [
            'id' => 1000,
            'name' => 'cnyuser',
            'email' => 'cnyuser@ninepinetech.com',
            'email_verified_at' => null,
            'password' => bcrypt('9pinesecurityuser'),
            'remember_token' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'firstname' => 'CNY',
            'lastname' => 'User',
            'phone' =>'0000000000',
            'postcode' => '00000',
            'state' => 'xxxxx',
            'city' => 'xxxxx',
            'address' => 'xxxxx',
            'country_id' => 45,
            'phone_country_code' => null,
            'currency_id' => 1,
            'birthdate' => null,
            'status' => 1,
            'is_reset' => 1,
            'is_vip' => false
        ];

        $cnyUser = User::find(1000);

        User::firstOrCreate(['id' => 1000], $data);

        if (!$cnyUser) {
            UserWallet::makeTransaction(1000, 1000000, 1, $sourceId, 'Credit');
        }

        $data = [
            'id' => 1001,
            'name' => 'usduser',
            'email' => 'usduser@ninepinetech.com',
            'email_verified_at' => null,
            'password' => bcrypt('9pinesecurityuser'),
            'remember_token' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'firstname' => 'USD',
            'lastname' => 'User',
            'phone' =>'0000000000',
            'postcode' => '00000',
            'state' => 'xxxxx',
            'city' => 'xxxxx',
            'address' => 'xxxxx',
            'country_id' => 233,
            'phone_country_code' => null,
            'currency_id' => 2,
            'birthdate' => null,
            'status' => 1,
            'is_reset' => 1,
            'is_vip' => false
        ];

        $userUser = User::find(1001);

        User::firstOrCreate(['id' => 1001], $data);

        if (!$userUser) {
            UserWallet::makeTransaction(1001, 1000000, 2, $sourceId, 'Credit');
        }
    }
}
