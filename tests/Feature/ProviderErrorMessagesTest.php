<?php

namespace Tests\Feature;

use App\User;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use Laravel\Passport\ClientRepository;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProviderErrorMessagesTest extends RegistrationTest
{
    use RefreshDatabase, WithFaker;

    protected $providerErrorMessages = [
        1  => 'The maximum bet amount for this event is 90',
        2  => 'Your bet was not placed. Please try again.',
        3  => 'Due to the heavy traffic on the website, please try again',
        4  => 'The event is currently closed for betting',
        5  => 'The odds have changed',
        6  => 'Internal Error: Session Inactive',
        7  => 'Rejected',
        8  => 'Abnormal Bets',
        9  => 'Bookmaker can\'t be reached',
        10 => 'Your bet is currently pending.',
        11 => 'Betting maintenance edit'
    ];

    /** @test */
    public function checkProviderErrorMapping()
    {
        $this->initialUser();
        $this->providerErrorMessageSeed();

        $providerErrorMessages = \DB::table('provider_error_messages')->pluck('id')->toArray();
        foreach ($this->providerErrorMessages as $key => $providerErrorMessage) {
            $order           = Order::create($this->newOrder('FAILED', $providerErrorMessage, $this->user_id));
            $providerErrorId = providerErrorMapping($order->reason);
            $this->assertContains($providerErrorId, $providerErrorMessages);
        }


    }

    protected function newOrder($status, $reason, $userId)
    {
        return [
            'user_id'                       => $userId,
            'master_event_market_id'        => $this->faker->randomDigitNotNull,
            'master_event_unique_id'        => "20200625-1-2-4170699",
            'master_event_market_unique_id' => $this->faker->md5,
            'market_id'                     => "REH4316969",
            'status'                        => $status,
            'bet_id'                        => "",
            'bet_selection'                 => $this->faker->sentence,
            'provider_id'                   => 1,
            'sport_id'                      => 1,
            'odds'                          => $this->faker->randomFloat(2),
            'odd_label'                     => "+0.25",
            'stake'                         => $this->faker->randomDigitNotNull,
            'to_win'                        => $this->faker->randomFloat(2),
            'settled_date'                  => null,
            'reason'                        => $reason,
            'profit_loss'                   => 0.00,
            'order_expiry'                  => 30,
            'provider_account_id'           => $this->faker->randomDigitNotNull,
            'ml_bet_identifier'             => "ML20200698000069",
            'score_on_bet'                  => "0 - 0",
            'odd_type_id'                   => $this->faker->randomDigitNotNull,
            'market_flag'                   => "HOME",
            'final_score'                   => null,
            'master_league_name'            => $this->faker->word,
            'master_team_home_name'         => $this->faker->word,
            'master_team_away_name'         => $this->faker->word
        ];
    }

    private function initialUser()
    {
        $clientRepository = new ClientRepository();
        $client           = $clientRepository->createPersonalAccessClient(
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
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => bcrypt($data['password']),
            'firstname'   => $data['firstname'],
            'lastname'    => $data['lastname'],
            'country_id'  => $data['country_id'],
            'currency_id' => $data['currency_id'],
            'status'      => 1
        ]);
        $user->save();
        $this->user_id = $user->id;
        $response      = $this->post('/api/v1/auth/login', [
            'email'    => $data['email'],
            'password' => $data['password']
        ], [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $this->loginJsonResponse = json_decode($response->getContent(), false);
    }

    public function providerErrorMessageSeed()
    {
        $data = [
            [
                'error_message_id' => 1,
                'message'          => "The maximum bet amount for this event is"
            ],
            [
                'error_message_id' => 1,
                'message'          => "Your bet was not placed. Please try again."
            ],
            [
                'error_message_id' => 1,
                'message'          => "Due to the heavy traffic on the website, please try again"
            ],
            [
                'error_message_id' => 1,
                'message'          => "The event is currently closed for betting"
            ],
            [
                'error_message_id' => 2,
                'message'          => "The odds have changed"
            ],
            [
                'error_message_id' => 3,
                'message'          => "Internal Error: Session Inactive"
            ],
            [
                'error_message_id' => 4,
                'message'          => "Rejected"
            ],
            [
                'error_message_id' => 6,
                'message'          => "Abnormal Bets"
            ],
            [
                'error_message_id' => 8,
                'message'          => "Bookmaker can't be reached"
            ],
            [
                'error_message_id' => 9,
                'message'          => "Your bet is currently pending."
            ],
            [
                'error_message_id' => 10,
                'message'          => "Betting maintenance edit"
            ]

        ];
        foreach ($data as $d) {
            DB::table('provider_error_messages')->insert($d);
        }
    }
}
