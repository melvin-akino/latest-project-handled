<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;

class UserDataAndConfigurationTest extends RegistrationTest
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function withValidTokenTest()
    {
        $this->initialUser();
        $response = $this->get('/api/v1/user', [
            'X-Requested-With' => 'XMLHttpRequest',
            'Authorization' => 'Bearer ' . $this->loginJsonResponse->access_token
        ]);

        $response->assertJson(['status' => true]);
        $response->assertJson(['status_code' => 200]);
    }

    /** @test */
    public function withInvalidTokenTest()
    {
        $response = $this->get('/api/v1/user', [
            'X-Requested-With' => 'XMLHttpRequest',
            'Authorization' => 'Bearer XXX'
        ]);

        $response->assertStatus(404);
    }

    private function initialUser()
    {
        $clientRepository = new ClientRepository();
        $client = $clientRepository->createPersonalAccessClient(
            null, 'Test Personal Access Client', env('API_URL')
        );

        DB::table('oauth_personal_access_clients')
            ->insert([
               'client_id'  => $client->id,
               'created_at' => Carbon::now(),
               'updated_at' => Carbon::now(),
           ]);

        $data = $this->data();
        $this->post('/api/v1/auth/register', $data, [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response = $this->post('/api/v1/auth/login', [
            'email'    => $data['email'],
            'password' => $data['password']
        ], [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $this->loginJsonResponse = json_decode($response->getContent(), false);
    }
}
