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
                [
                    "uid" => "asdasd",
                    "sport_id" => 1,
                    "sport" => "Soccer",
                    "provider_id" => 1,
                    "game_schedule" => "inplay",
                    "league_name" => "English Football League",
                    "home" => [
                        "name" => "Glenorchy Knights",
                        "score" => 0,
                        "redcard" => 0
                    ],
                    "away" => [
                        "name" => "Kingborough Lions United",
                        "score" => 0,
                        "redcard" => 1
                    ],
                    "ref_schedule" => "2020-02-13 08:00:00",
                    "running_time" => "2H 20:58",
                    "market_odds" => [
                        "main" => [
                            "1X2" => [
                                "home" => [
                                    "odds" => 1.23,
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 2.23,
                                    "market_id" => "asd123"
                                ],
                                "draw" => [
                                    "odds" => 3.23,
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HDP" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "OU" => [
                                "home" => [
                                    "odds" => 6.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "OE" => [
                                "home" => [
                                    "odds" => 8.23,
                                    "points" => "O",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 9.23,
                                    "points" => "E",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HT 1X2" => [
                                "home" => [
                                    "odds" => 1.23,
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 2.23,
                                    "market_id" => "asd123"
                                ],
                                "draw" => [
                                    "odds" => 3.23,
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HT HDP" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HT OU" => [
                                "home" => [
                                    "odds" => 6.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd123"
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    "uid" => "123123",
                    "sport_id" => 1,
                    "sport" => "Soccer",
                    "provider_id" => 1,
                    "game_schedule" => "inplay",
                    "league_name" => "Football League A",
                    "home" => [
                        "name" => "Brazil",
                        "score" => 0,
                        "redcard" => 0
                    ],
                    "away" => [
                        "name" => "Argentina",
                        "score" => 0,
                        "redcard" => 1
                    ],
                    "ref_schedule" => "2020-02-13 08:00:00",
                    "running_time" => "2H 20:58",
                    "market_odds" => [
                        "main" => [
                            "1X2" => [
                                "home" => [
                                    "odds" => 1.23,
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 2.23,
                                    "market_id" => "asd123"
                                ],
                                "draw" => [
                                    "odds" => 3.23,
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HDP" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "OU" => [
                                "home" => [
                                    "odds" => 6.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "OE" => [
                                "home" => [
                                    "odds" => 8.23,
                                    "points" => "O",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 9.23,
                                    "points" => "E",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HT 1X2" => [
                                "home" => [
                                    "odds" => 1.23,
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 2.23,
                                    "market_id" => "asd123"
                                ],
                                "draw" => [
                                    "odds" => 3.23,
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HT HDP" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HT OU" => [
                                "home" => [
                                    "odds" => 6.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd123"
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    "uid" => "696969",
                    "sport_id" => 1,
                    "sport" => "Soccer",
                    "provider_id" => 1,
                    "game_schedule" => "inplay",
                    "league_name" => "Football League A",
                    "home" => [
                        "name" => "England",
                        "score" => 0,
                        "redcard" => 0
                    ],
                    "away" => [
                        "name" => "Germany",
                        "score" => 0,
                        "redcard" => 1
                    ],
                    "ref_schedule" => "2020-02-13 08:00:00",
                    "running_time" => "2H 20:58",
                    "market_odds" => [
                        "main" => [
                            "1X2" => [
                                "home" => [
                                    "odds" => 8.23,
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 9.23,
                                    "market_id" => "asd123"
                                ],
                                "draw" => [
                                    "odds" => 1.23,
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HDP" => [
                                "home" => [
                                    "odds" => 2.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 3.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "OU" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "OE" => [
                                "home" => [
                                    "odds" => 6.23,
                                    "points" => "O",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "points" => "E",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HT 1X2" => [
                                "home" => [
                                    "odds" => 8.23,
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 9.23,
                                    "market_id" => "asd123"
                                ],
                                "draw" => [
                                    "odds" => 1.23,
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HT HDP" => [
                                "home" => [
                                    "odds" => 2.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 3.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HT OU" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd123"
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    "uid" => "420420",
                    "sport_id" => 1,
                    "sport" => "Soccer",
                    "provider_id" => 1,
                    "game_schedule" => "inplay",
                    "league_name" => "FIFA",
                    "home" => [
                        "name" => "Malaysia",
                        "score" => 0,
                        "redcard" => 0
                    ],
                    "away" => [
                        "name" => "Japan",
                        "score" => 0,
                        "redcard" => 1
                    ],
                    "ref_schedule" => "2020-02-13 08:00:00",
                    "running_time" => "2H 20:58",
                    "market_odds" => [
                        "main" => [
                            "1X2" => [
                                "home" => [
                                    "odds" => 6.23,
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "market_id" => "asd123"
                                ],
                                "draw" => [
                                    "odds" => 8.23,
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HDP" => [
                                "home" => [
                                    "odds" => 9.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 1.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "OU" => [
                                "home" => [
                                    "odds" => 2.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 3.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "OE" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "O",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "E",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HT 1X2" => [
                                "home" => [
                                    "odds" => 6.23,
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "market_id" => "asd123"
                                ],
                                "draw" => [
                                    "odds" => 8.23,
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HT HDP" => [
                                "home" => [
                                    "odds" => 9.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 1.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd123"
                                ]
                            ],
                            "HT OU" => [
                                "home" => [
                                    "odds" => 2.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd123"
                                ],
                                "away" => [
                                    "odds" => 3.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd123"
                                ]
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
