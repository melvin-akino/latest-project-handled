<?php

namespace App\Http\Controllers;

use App\Models\Sport;

use Illuminate\Http\Request;
use Faker\Factory AS Faker;

class TradeController extends Controller
{
    /**
     * Fetch Authenticated User's Lists of Open Orders
     *
     * @return json
     */
    public function getUserBetbar()
    {
        try {
            $data = [
                'status'      => true,
                'status_code' => 200,
                'data'        => [
                    "bet_id_20"        => [
                        'league_name' => "FIFA Asia 2020",
                        'home'        => "Vietnam",
                        'away'        => "South Korea",
                        'bet_info'    => [
                            'home',
                            'FT 1X2',
                            '1.54',
                            '120'
                        ],
                        'status'      => "Processing",
                        'created_at'  => "2020-02-11 4:20 PM",
                    ],
                    "bet_id_19"        => [
                        'league_name' => "FIFA Asia 2020",
                        'home'        => "Philippines",
                        'away'        => "India",
                        'bet_info'    => [
                            'away',
                            'FT 1X2',
                            '2.58',
                            '80'
                        ],
                        'status'      => "Success",
                        'created_at'  => "2020-02-11 4:10 PM",
                    ],
                ],
            ];

            return response()->json($data, 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /**
     * Fetch Authenticated User's Lists of Favorite League Events
     *
     * @return json
     */
    public function getUserWatchlist()
    {
        try {
            /** TO DO: Include Logic for fetching User Watchlist game events */

            $data = [
                "Football League A" => [
                    [
                        "uid"            => "20200312-1-1",
                        "game_schedule"  => "3",
                        "home_team_name" => "Los Angeles Lakers",
                        "away_team_name" => "Los Angeles Clippers",
                        "1X2"         => [
                            "home" => [
                                'odds' => 1.37,
                                'bet_id' => 'EFUWIHXIUEH'
                            ],
                            "away" => [
                                'odds' => 1.43,
                                'bet_id' => 'GFGRESDSDSD'
                            ],
                            "draw" => [
                                'odds' => 3.73,
                                'bet_id' => 'TEUIYUDHJFF'
                            ],
                        ],
                        "HDP"         => [
                            "home" => [
                                'odds' => 3.32,
                                'points' => 2,
                                'bet_id' => 'XIJHJGLKJLKD'
                            ],
                            "away" => [
                                'odds' => 1.74,
                                'points' => 2.5,
                                'bet_id' => 'TEIOUWIENKDS'
                            ]
                        ],
                        "OU"          => [
                            "home" => [
                                'odds' => 2.65,
                                'points' => 'O 1.5',
                                'bet_id' => 'GDSDKDJLKSDJ'
                            ],
                            "away" => [
                                'odds' => 1.74,
                                'points' => 'U 2.5',
                                'bet_id' => 'FDFDAEFDFDSD'
                            ]
                        ]
                    ],
                    [
                        "uid"            => "20200312-1-2",
                        "game_schedule"  => "3",
                        "home_team_name" => "Cleveland Cavaliers",
                        "away_team_name" => "Indiana Pacers",
                        "1X2"         => [
                            "home" => [
                                'odds' => 1.11,
                                'bet_id' => 'EFUWIHXIUEH'
                            ],
                            "away" => [
                                'odds' => 1.23,
                                'bet_id' => 'GFGRESDSDSD'
                            ],
                            "draw" => [
                                'odds' => 2.87,
                                'bet_id' => 'TEUIYUDHJFF'
                            ],
                        ],
                        "HDP"         => [
                            "home" => [
                                'odds' => 1.45,
                                'points' => 2,
                                'bet_id' => 'XIJHJGLKJLKD'
                            ],
                            "away" => [
                                'odds' => 4.34,
                                'points' => 2.5,
                                'bet_id' => 'TEIOUWIENKDS'
                            ]
                        ],
                        "OU"          => [
                            "home" => [
                                'odds' => 2.76,
                                'points' => 'O 1.5',
                                'bet_id' => 'GDSDKDJLKSDJ'
                            ],
                            "away" => [
                                'odds' => 1.74,
                                'points' => 'U 2.5',
                                'bet_id' => 'FDFDAEFDFDSD'
                            ]
                        ]
                    ],
                ],
                "Football League B" => [
                    [
                        "uid"            => "20200312-2-1",
                        "game_schedule"  => "2",
                        "home_team_name" => "Chicago Bulls",
                        "away_team_name" => "Miami Heat",
                        "1X2"         => [
                            "home" => [
                                'odds' => 1.32,
                                'bet_id' => 'EFUWIHXIUEH'
                            ],
                            "away" => [
                                'odds' => 1.34,
                                'bet_id' => 'GFGRESDSDSD'
                            ],
                            "draw" => [
                                'odds' => 2.12,
                                'bet_id' => 'TEUIYUDHJFF'
                            ],
                        ],
                        "HDP"         => [
                            "home" => [
                                'odds' => 4.45,
                                'points' => 2,
                                'bet_id' => 'XIJHJGLKJLKD'
                            ],
                            "away" => [
                                'odds' => 2.34,
                                'points' => 2.5,
                                'bet_id' => 'TEIOUWIENKDS'
                            ]
                        ],
                        "OU"          => [
                            "home" => [
                                'odds' => 6.76,
                                'points' => 'O 1.5',
                                'bet_id' => 'GDSDKDJLKSDJ'
                            ],
                            "away" => [
                                'odds' => 2.74,
                                'points' => 'U 2.5',
                                'bet_id' => 'FDFDAEFDFDSD'
                            ]
                        ]
                    ]
                ]
            ];
            
            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => $data
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /**
     * Add to Authenticated User's Lists of Favorite League Events
     *
     * @return json
     */
    public function postAddToWatchlist(Request $request)
    {
        try {
            /** TO DO: Include Logic for adding game events to User Watchlist */

            $data = [];

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('game.watchlist.success')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /**
     * Remove to Authenticated User's Lists of Favorite League Events
     *
     * @return json
     */
    public function postRemoveToWatchlist(Request $request)
    {
        try {
            /** TO DO: Include Logic for removing game events to User Watchlist */

            $data = [];

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('game.watchlist.removed')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /**
     * Get Leagues per Authenticated User's default Sport
     *
     * @return json
     */
    public function getInitialLeagues()
    {
        try {
            /** Get Authenticated User's Default Initial Sport : Last Sport visited */
            $data = getUserDefault(auth()->user()->id, 'sport');

            /** Temporary Dummy Data */
            $leagues = $this->loopLeagues($data['default_sport']);

            if (!$data['status']) {
                return response()->json([
                    'status'      => false,
                    'status_code' => 400,
                    'message'     => $data['error']
                ]);
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'sport_id'    => $data['default_sport'],
                'data'        => $leagues
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }

    /**
     * Temporary produce dummy data for Multiline Leagues
     *
     * @param  int    $sportId
     * @return json
     */
    private function loopLeagues(int $sportId)
    {
        $data = [];
        $faker = Faker::create();
        $sportName = Sport::find($sportId)->details;
        $gameSchedules = [
            'inplay',
            'today',
            'early'
        ];

        foreach ($gameSchedules AS $row) {
            for ($i = 0; $i < rand(2, 12); $i++) {
                $data[$row][] = [
                    'name'        => $faker->country . " " . $sportName,
                    'match_count' => rand(1, 8),
                ];
            }
        }

        return $data;
    }
}
