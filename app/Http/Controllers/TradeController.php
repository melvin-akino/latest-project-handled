<?php

namespace App\Http\Controllers;

use App\Models\{MasterEvent, MasterLeague, Sport, UserSelectedLeague};

use Illuminate\Support\Facades\DB;
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
                                    "market_id" => "asd1231"
                                ],
                                "away" => [
                                    "odds" => 2.23,
                                    "market_id" => "asd1232"
                                ],
                                "draw" => [
                                    "odds" => 3.23,
                                    "market_id" => "asd1233"
                                ]
                            ],
                            "HDP" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd1234"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd1235"
                                ]
                            ],
                            "OU" => [
                                "home" => [
                                    "odds" => 6.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd1236"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd1237"
                                ]
                            ],
                            "OE" => [
                                "home" => [
                                    "odds" => 8.23,
                                    "points" => "O",
                                    "market_id" => "asd1238"
                                ],
                                "away" => [
                                    "odds" => 9.23,
                                    "points" => "E",
                                    "market_id" => "asd1239"
                                ]
                            ],
                            "HT 1X2" => [
                                "home" => [
                                    "odds" => 1.23,
                                    "market_id" => "asd12310"
                                ],
                                "away" => [
                                    "odds" => 2.23,
                                    "market_id" => "asd12311"
                                ],
                                "draw" => [
                                    "odds" => 3.23,
                                    "market_id" => "asd12312"
                                ]
                            ],
                            "HT HDP" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd12313"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd12314"
                                ]
                            ],
                            "HT OU" => [
                                "home" => [
                                    "odds" => 6.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd12315"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd12316"
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
                                    "market_id" => "asd12317"
                                ],
                                "away" => [
                                    "odds" => 2.23,
                                    "market_id" => "asd12318"
                                ],
                                "draw" => [
                                    "odds" => 3.23,
                                    "market_id" => "asd12319"
                                ]
                            ],
                            "HDP" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd12320"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd12321"
                                ]
                            ],
                            "OU" => [
                                "home" => [
                                    "odds" => 6.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd12322"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd12323"
                                ]
                            ],
                            "OE" => [
                                "home" => [
                                    "odds" => 8.23,
                                    "points" => "O",
                                    "market_id" => "asd12324"
                                ],
                                "away" => [
                                    "odds" => 9.23,
                                    "points" => "E",
                                    "market_id" => "asd12325"
                                ]
                            ],
                            "HT 1X2" => [
                                "home" => [
                                    "odds" => 1.23,
                                    "market_id" => "asd12326"
                                ],
                                "away" => [
                                    "odds" => 2.23,
                                    "market_id" => "asd12327"
                                ],
                                "draw" => [
                                    "odds" => 3.23,
                                    "market_id" => "asd12328"
                                ]
                            ],
                            "HT HDP" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd12329"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd12330"
                                ]
                            ],
                            "HT OU" => [
                                "home" => [
                                    "odds" => 6.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd12331"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd12332"
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
                                    "market_id" => "asd12333"
                                ],
                                "away" => [
                                    "odds" => 9.23,
                                    "market_id" => "asd12334"
                                ],
                                "draw" => [
                                    "odds" => 1.23,
                                    "market_id" => "asd12335"
                                ]
                            ],
                            "HDP" => [
                                "home" => [
                                    "odds" => 2.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd12336"
                                ],
                                "away" => [
                                    "odds" => 3.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd12337"
                                ]
                            ],
                            "OU" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd12338"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd12339"
                                ]
                            ],
                            "OE" => [
                                "home" => [
                                    "odds" => 6.23,
                                    "points" => "O",
                                    "market_id" => "asd12340"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "points" => "E",
                                    "market_id" => "asd12341"
                                ]
                            ],
                            "HT 1X2" => [
                                "home" => [
                                    "odds" => 8.23,
                                    "market_id" => "asd12342"
                                ],
                                "away" => [
                                    "odds" => 9.23,
                                    "market_id" => "asd12343"
                                ],
                                "draw" => [
                                    "odds" => 1.23,
                                    "market_id" => "asd12344"
                                ]
                            ],
                            "HT HDP" => [
                                "home" => [
                                    "odds" => 2.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd12345"
                                ],
                                "away" => [
                                    "odds" => 3.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd12346"
                                ]
                            ],
                            "HT OU" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd12347"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd12348"
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
                                    "market_id" => "asd12349"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "market_id" => "asd12350"
                                ],
                                "draw" => [
                                    "odds" => 8.23,
                                    "market_id" => "asd12351"
                                ]
                            ],
                            "HDP" => [
                                "home" => [
                                    "odds" => 9.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd12352"
                                ],
                                "away" => [
                                    "odds" => 1.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd12353"
                                ]
                            ],
                            "OU" => [
                                "home" => [
                                    "odds" => 2.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd12354"
                                ],
                                "away" => [
                                    "odds" => 3.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd12355"
                                ]
                            ],
                            "OE" => [
                                "home" => [
                                    "odds" => 4.23,
                                    "points" => "O",
                                    "market_id" => "asd12356"
                                ],
                                "away" => [
                                    "odds" => 5.23,
                                    "points" => "E",
                                    "market_id" => "asd12357"
                                ]
                            ],
                            "HT 1X2" => [
                                "home" => [
                                    "odds" => 6.23,
                                    "market_id" => "asd12358"
                                ],
                                "away" => [
                                    "odds" => 7.23,
                                    "market_id" => "asd12359"
                                ],
                                "draw" => [
                                    "odds" => 8.23,
                                    "market_id" => "asd12360"
                                ]
                            ],
                            "HT HDP" => [
                                "home" => [
                                    "odds" => 9.23,
                                    "points" => "-2.5",
                                    "market_id" => "asd12361"
                                ],
                                "away" => [
                                    "odds" => 1.23,
                                    "points" => "+2.5",
                                    "market_id" => "asd12362"
                                ]
                            ],
                            "HT OU" => [
                                "home" => [
                                    "odds" => 2.23,
                                    "points" => "O 2.5",
                                    "market_id" => "asd12363"
                                ],
                                "away" => [
                                    "odds" => 3.23,
                                    "points" => "U 2.5",
                                    "market_id" => "asd12364"
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
     * Add/Remove to Authenticated User's Lists of Favorite Leagues/Events
     *
     * @param  $action  "add|remove"
     * @param  $request \Illuminate\Http\Request
     * @return json
     */
    public function postManageWatchlist($action, Request $request)
    {
        try {
            /** TO DO: Include Logic for adding game events to User Watchlist */
            $data = [];
            $lang = "";

            switch ($request->type) {
                case 'league':
                    $leagueId       = MasterLeague::getIdByName($request->data);
                    $masterEventIds = MasterEvent::getActiveEvents('master_league_id', '=', $leagueId)->get('id')->toArray();
                    break;

                case 'event':
                    $masterEventIds = MasterEvent::getActiveEvents('master_event_unique_id', '=', $request->data)->get('id')->toArray();
                    break;
            }

            if ($action == "add") {
                $lang = "added";

                foreach ($masterEventIds AS $row) {
                    UserWatchlist::create(
                        [
                            'user_id'         => auth()->user()->id,
                            'master_event_id' => $row
                        ]
                    );
                }
            }

            if ($action == "remove") {
                $lang = "removed";

                UserWatchlist::where('user_id', auth()->user()->id)
                    ->whereIn('master_event_id', $masterEventIds)
                    ->delete();
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('game.watchlist.' . $lang)
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

    /**
     * Add/Remove Authenticated User's Selected Sidebar Leagues
     *
     * @param  $request \Illuminate\Http\Request
     * @return json
     */
    public function postManageSidebarLeagues(Request $request)
    {
        try {
            $leagueId   = MasterLeague::getIdByName($request->data);
            $checkTable = UserSelectedLeague::where('user_id', auth()->user()->id)
                ->where('master_league_id', $leagueId);

            if ($checkTable->count() == 0) {
                UserSelectedLeague::create(
                    [
                        'user_id'          => auth()->user()->id,
                        'master_league_id' => $leagueId
                    ]
                );
            } else {
                $checktable->delete();
            }

            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'message'     => trans('notifications.save.success')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }
}
