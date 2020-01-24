<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\{ RefreshDatabase, WithFaker };
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;

class UserSettingsTest extends RegistrationTest
{
    use RefreshDatabase, WithFaker;

    protected $oddsConfig = ['bet-columns'];
    protected $provConfig = ['bookies'];
    protected $userConfig = ['general', 'trade-page', 'bet-slip', 'notifications-and-sounds', 'language'];

    /** @test */
    public function updateUserConfigWithValidTokenTest()
    {
        $this->initialUser();
        $_userConfig = $this->userConfig;

        collect($_userConfig)
            ->each(function ($type) use ($_userConfig) {
                $response = $this->post(
                    '/api/v1/user/settings/' . $type,
                    config('default_config.' . $type),
                    [ 'Authorization' => 'Bearer ' . $this->loginJsonResponse->access_token ]
                );

                $response->assertJson(['status' => true]);
                $response->assertJson(['status_code' => 200]);
            });
    }

    /** @test */
    public function updateProvConfigWithValidTokenTest()
    {
        $this->initialUser();

        $response = $this->post(
            '/api/v1/user/settings/bookies',
            config('default_config.bookies'),
            [ 'Authorization' => 'Bearer ' . $this->loginJsonResponse->access_token ]
        );

        $response->assertJson(['status' => true]);
        $response->assertJson(['status_code' => 200]);
    }

    /** @test */
    public function updateOddsConfigWithValidTokenTest()
    {
        $this->initialUser();

        $response = $this->post(
            '/api/v1/user/settings/bet-columns',
            config('default_config.bet-columns'),
            [ 'Authorization' => 'Bearer ' . $this->loginJsonResponse->access_token ]
        );

        $response->assertJson(['status' => true]);
        $response->assertJson(['status_code' => 200]);
    }

    /** @test */
    public function resetUserSettingsWithValidTokenTest()
    {
        $this->initialUser();

        $response = $this->post(
            '/api/v1/user/settings/reset',
            [],
            [ 'Authorization' => 'Bearer ' . $this->loginJsonResponse->access_token ]
        );

        $response->assertJson(['status' => true]);
        $response->assertJson(['status_code' => 200]);
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
        $this->post('/api/v1/auth/register', $data);

        $response = $this->post('/api/v1/auth/login', [
            'email'    => $data['email'],
            'password' => $data['password']
        ]);

        $this->loginJsonResponse = json_decode($response->getContent(), false);
    }
}
