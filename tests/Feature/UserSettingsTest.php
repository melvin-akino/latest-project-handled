<?php

namespace Tests\Feature;

use App\User;
use App\Models\{Provider, Sport, SportOddType};
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
                    [
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Authorization'    => 'Bearer ' . $this->loginJsonResponse->access_token
                    ]
                );

                $response->assertJson(['status' => true]);
                $response->assertJson(['status_code' => 200]);
            });
    }

    /** @test */
    public function updateProvConfigWithValidTokenTest()
    {
        $this->initialUser();

        $providers = Provider::getActiveProviders()->get()->toArray();
        $params = [];
        foreach ($providers as $provider) {
            $params[] = [
                'provider_id' => $provider['id'],
                'active' => true
            ];
        }
        $response = $this->post(
            '/api/v1/user/settings/bookies',
            $params,
            [
                'X-Requested-With' => 'XMLHttpRequest',
                'Authorization'    => 'Bearer ' . $this->loginJsonResponse->access_token
            ]
        );

        $response->assertJson(['status' => true]);
        $response->assertJson(['status_code' => 200]);
    }

    /** @test */
    public function updateOddsConfigWithValidTokenTest()
    {
        $this->initialUser();

        $sportOddTypes = SportOddType::getEnabledSportOdds();
        $params = [];
        foreach ($sportOddTypes as $sportOddType) {
            $params[] = [
                'sport_odd_type_id' => $sportOddType->id,
                'active' => true
            ];
        }

        $response = $this->post(
            '/api/v1/user/settings/bet-columns',
            $params,
            [
                'X-Requested-With' => 'XMLHttpRequest',
                'Authorization'    => 'Bearer ' . $this->loginJsonResponse->access_token
            ]
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
            [
                'X-Requested-With' => 'XMLHttpRequest',
                'Authorization'    => 'Bearer ' . $this->loginJsonResponse->access_token
            ]
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
        ]);

        $this->loginJsonResponse = json_decode($response->getContent(), false);
    }

    /** @test */
    public function updateColumnPerSportConfigWithValidTokenTest()
    {
        $this->initialUser();
        $sportId = Sport::inRandomOrder()->first()->id;

        $sportOddTypes = SportOddType::getEnabledSportOdds($sportId);
        $params = [];

        foreach ($sportOddTypes as $sportOddType) {
            $params[] = [
                'sport_odd_type_id' => $sportOddType->id,
                'active' => true
            ];
        }

        $response = $this->post(
            '/api/v1/user/settings/bet-columns/' . $sportId,
            $params,
            [
                'X-Requested-With' => 'XMLHttpRequest',
                'Authorization'    => 'Bearer ' . $this->loginJsonResponse->access_token
            ]
        );

        $response->assertJson(['status' => true]);
        $response->assertJson(['status_code' => 200]);
    }

    /** @test */
    public function userWalletTest()
    {
        $this->initialUser();

        $response = $this->get(
            '/api/v1/user/wallet',
            [
                'X-Requested-With' => 'XMLHttpRequest',
                'Authorization'    => 'Bearer ' . $this->loginJsonResponse->access_token
            ]
        );

        $response->assertJson(['status' => true]);
        $response->assertJson(['status_code' => 200]);
    }

    /** @test */
    public function userBetbarTest()
    {
        $this->initialUser();

        $response = $this->get(
            '/api/v1/trade/betbar',
            [
                'X-Requested-With' => 'XMLHttpRequest',
                'Authorization'    => 'Bearer ' . $this->loginJsonResponse->access_token
            ]
        );

        $response->assertJson(['status' => true]);
        $response->assertJson(['status_code' => 200]);
    }
}
