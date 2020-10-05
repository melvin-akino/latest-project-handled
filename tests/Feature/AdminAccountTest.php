<?php

namespace Tests\Feature;

use App\Models\CRM\User;
use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $followRedirects = true;

    public $user;

    /** @test */
    public function LoginwithFakeUser(){
        $email    = $this->faker->email;
        $password = bcrypt('testcase');

        $this->user = new User([
            'id'         => 1,
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'email'      => $email,
            'status_id'  => 1,
            'password'   => $password
        ]);

        $this->be($this->user);
        $this->assertTrue(true);
    }
}
