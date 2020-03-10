<?php

use App\Models\CRM\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'first_name' => "Super",
            'last_name'  => "Admin",
            'email'      => "superadmin@ninepinetech.com",
            'password'   => bcrypt('9pinesecurity@dmin'),
            'status_id'  => 1.
        ];

        User::create($data);
    }
}
