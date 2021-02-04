<?php

namespace Tests\Feature;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\{ RefreshDatabase, WithFaker };
use Laravel\Passport\ClientRepository;
use Illuminate\Support\Facades\DB;

class LeaguesTest extends RegistrationTest
{
    use RefreshDatabase, WithFaker;
   /** @test */
    public function withValidTokenOrderTest()
    {
        $this->initialUser();
        $response = $this->get('/api/v1/leagues/list', [
            'X-Requested-With' => 'XMLHttpRequest',
            'Authorization'    => 'Bearer ' . $this->loginJsonResponse->access_token
        ]);


        $response->assertJson(['status' => true]);
        $response->assertJson(['status_code' => 200]);
    }

    public function withInvalidTokenOrderTest() {
        $response = $this->get('/api/v1/leagues/list', [
            'X-Requested-With' => 'XMLHttpRequest',
            'Authorization'    => 'Bearer XXX'
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

        $user = new User([
            'name'                  => $data['name'],
            'email'                 => $data['email'],
            'password'              => bcrypt($data['password']),
            'firstname'             => $data['firstname'],
            'lastname'              => $data['lastname'],
            'country_id'            => $data['country_id'],
            'currency_id'           => $data['currency_id'],
            'status'                => 1
        ]);
        $user->save();

        $response = $this->post('/api/v1/auth/login', [
            'email'    => $data['email'],
            'password' => $data['password']
        ], [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $this->loginJsonResponse = json_decode($response->getContent(), false);
    }
}
