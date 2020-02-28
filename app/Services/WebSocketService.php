<?php

namespace App\Services;

use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use League\OAuth2\Server\ResourceServer;
use Laravel\Passport\Guards\TokenGuard;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\ClientRepository;
use Illuminate\Http\Request as HttpRequest;

class WebSocketService implements WebSocketHandlerInterface
{
    public function __construct()
    {
    }

    public function onOpen(Server $server, Request $request)
    {
        $user = $this->getUser($request->get['token']);
        $userId = $user ? $user['id'] : 0;

        $server->wsTable->set('uid:' . $userId, ['value' => $request->fd]);
        $server->wsTable->set('fd:' . $request->fd, ['value' => $userId]);

        $server->push($request->fd, 'Welcome to LaravelS');
    }

    public function onMessage(Server $server, Frame $frame)
    {
        // $user = $server->wsTable->get('fd:' . $frame->fd);

        // $commands = [
        //     'getUserSport'         => 'App\Jobs\WsUserSport',
        //     'getSelectedLeagues'   => 'App\Jobs\WsSelectedLeagues',
        //     'getAdditionalLeagues' => 'App\Jobs\WsAdditionalLeagues',
        //     'getForRemovalLeagues' => 'App\Jobs\WsForRemovalLeagues',
        //     'getWatchlist'         => 'App\Jobs\WsWatchlist',
        //     'getEvents'            => 'App\Jobs\WsEvents'
        // ];
        // $commandFound = false;
        // foreach ($commands as $key => $value) {
        //     $clientCommand = explode('_', $frame->data);
        //     if ($clientCommand[0] == $key) {
        //         $commandFound = true;
        //         $job = $commands[$clientCommand[0]];
        //         if (count($clientCommand) > 0) {
        //             $job::dispatch($user['value'], $clientCommand);
        //             Log::debug("WS Job Dispatched");
        //         } else {
        //             $job::dispatch($user['value']);
        //         }
        //         break;
        //     }
        // }
        // if ($commandFound) {
        //     wsEmit("Found");
        // }

        // Sample Data to Emit
        $additionalLeagues = [
            'inplay' => [
                [
                    'name' => 'Premier League',
                    'match_count' => 69
                ],
                [
                    'name' => 'English Football League',
                    'match_count' => 1
                ],
                [
                    'name' => 'EFL Championship',
                    'match_count' => 8
                ]
            ],
            'today' => [
                [
                    'name' => 'Serie A',
                    'match_count' => 8
                ],
                [
                    'name' => 'Bundesliga',
                    'match_count' => 1
                ],
                [
                    'name' => 'UEFA',
                    'match_count' => 10
                ],
                [
                    'name' => 'La Liga',
                    'match_count' => 24
                ]
            ],
            'early' => [
                [
                    'name' => 'Major League Soccer',
                    'match_count' => 50
                ],
                [
                    'name' => 'FIFA',
                    'match_count' => 1
                ]
            ]
        ];

        $watchlist = [
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
                "league_name" => "Finland Cup",
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
            ]
        ];

        $events = [
            [
                "uid" => "QWERTY",
                "sport_id" => 1,
                "sport" => "Soccer",
                "provider_id" => 1,
                "game_schedule" => "inplay",
                "league_name" => "Portugal Liga Pro",
                "home" => [
                    "name" => "Manchester United",
                    "score" => 0,
                    "redcard" => 0
                ],
                "away" => [
                    "name" => "Liverpool United",
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
                "uid" => "TYUIOP",
                "sport_id" => 1,
                "sport" => "Soccer",
                "provider_id" => 1,
                "game_schedule" => "today",
                "league_name" => "Bundesliga",
                "home" => [
                    "name" => "Portugal",
                    "score" => 0,
                    "redcard" => 0
                ],
                "away" => [
                    "name" => "Spain",
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
                "uid" => "ASDFG",
                "sport_id" => 1,
                "sport" => "Soccer",
                "provider_id" => 1,
                "game_schedule" => "early",
                "league_name" => "FIFA",
                "home" => [
                    "name" => "Philippines",
                    "score" => 0,
                    "redcard" => 0
                ],
                "away" => [
                    "name" => "South Korea",
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
                                "market_id" => "asd12333"
                            ],
                            "away" => [
                                "odds" => 7.23,
                                "market_id" => "asd12334"
                            ],
                            "draw" => [
                                "odds" => 8.23,
                                "market_id" => "asd12335"
                            ]
                        ],
                        "HDP" => [
                            "home" => [
                                "odds" => 9.23,
                                "points" => "-2.5",
                                "market_id" => "asd12336"
                            ],
                            "away" => [
                                "odds" => 1.23,
                                "points" => "+2.5",
                                "market_id" => "asd12337"
                            ]
                        ],
                        "OU" => [
                            "home" => [
                                "odds" => 2.23,
                                "points" => "O 2.5",
                                "market_id" => "asd12338"
                            ],
                            "away" => [
                                "odds" => 3.23,
                                "points" => "U 2.5",
                                "market_id" => "asd12339"
                            ]
                        ],
                        "OE" => [
                            "home" => [
                                "odds" => 4.23,
                                "points" => "O",
                                "market_id" => "asd12340"
                            ],
                            "away" => [
                                "odds" => 5.23,
                                "points" => "E",
                                "market_id" => "asd12341"
                            ]
                        ],
                        "HT 1X2" => [
                            "home" => [
                                "odds" => 6.23,
                                "market_id" => "asd12342"
                            ],
                            "away" => [
                                "odds" => 7.23,
                                "market_id" => "asd12343"
                            ],
                            "draw" => [
                                "odds" => 8.23,
                                "market_id" => "asd12344"
                            ]
                        ],
                        "HT HDP" => [
                            "home" => [
                                "odds" => 9.23,
                                "points" => "-2.5",
                                "market_id" => "asd12345"
                            ],
                            "away" => [
                                "odds" => 1.23,
                                "points" => "+2.5",
                                "market_id" => "asd12346"
                            ]
                        ],
                        "HT OU" => [
                            "home" => [
                                "odds" => 2.23,
                                "points" => "O 2.5",
                                "market_id" => "asd12347"
                            ],
                            "away" => [
                                "odds" => 3.23,
                                "points" => "U 2.5",
                                "market_id" => "asd12348"
                            ]
                        ]
                    ]
                ]
            ],
            [
                "uid" => "HJKL",
                "sport_id" => 1,
                "sport" => "Soccer",
                "provider_id" => 1,
                "game_schedule" => "today",
                "league_name" => "Bundesliga",
                "home" => [
                    "name" => "Chile",
                    "score" => 0,
                    "redcard" => 0
                ],
                "away" => [
                    "name" => "Uruguay",
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
                                "market_id" => "asd12349"
                            ],
                            "away" => [
                                "odds" => 2.23,
                                "market_id" => "asd12350"
                            ],
                            "draw" => [
                                "odds" => 3.23,
                                "market_id" => "asd12351"
                            ]
                        ],
                        "HDP" => [
                            "home" => [
                                "odds" => 4.23,
                                "points" => "-2.5",
                                "market_id" => "asd12352"
                            ],
                            "away" => [
                                "odds" => 5.23,
                                "points" => "+2.5",
                                "market_id" => "asd12353"
                            ]
                        ],
                        "OU" => [
                            "home" => [
                                "odds" => 6.23,
                                "points" => "O 2.5",
                                "market_id" => "asd12354"
                            ],
                            "away" => [
                                "odds" => 7.23,
                                "points" => "U 2.5",
                                "market_id" => "asd12355"
                            ]
                        ],
                        "OE" => [
                            "home" => [
                                "odds" => 8.23,
                                "points" => "O",
                                "market_id" => "asd12356"
                            ],
                            "away" => [
                                "odds" => 9.23,
                                "points" => "E",
                                "market_id" => "asd12357"
                            ]
                        ],
                        "HT 1X2" => [
                            "home" => [
                                "odds" => 1.23,
                                "market_id" => "asd12358"
                            ],
                            "away" => [
                                "odds" => 2.23,
                                "market_id" => "asd12359"
                            ],
                            "draw" => [
                                "odds" => 3.23,
                                "market_id" => "asd12360"
                            ]
                        ],
                        "HT HDP" => [
                            "home" => [
                                "odds" => 4.23,
                                "points" => "-2.5",
                                "market_id" => "asd12361"
                            ],
                            "away" => [
                                "odds" => 5.23,
                                "points" => "+2.5",
                                "market_id" => "asd12362"
                            ]
                        ],
                        "HT OU" => [
                            "home" => [
                                "odds" => 6.23,
                                "points" => "O 2.5",
                                "market_id" => "asd12363"
                            ],
                            "away" => [
                                "odds" => 7.23,
                                "points" => "U 2.5",
                                "market_id" => "asd12364"
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $updatedOdds = [
            [
                "odds" => 2.23,
                "market_id" => "asd1231"
            ],
            [
                "odds" => 1.24,
                "market_id" => "asd1232"
            ],
            [
                "odds" => 6.29,
                "market_id" => "asd1233"
            ],
            [
                "odds" => 7.5,
                "market_id" => "asd1234"
            ],
            [
                "odds" => 2.24,
                "market_id" => "asd1235"
            ],
            [
                "odds" => 3.6,
                "market_id" => "asd1236"
            ],
            [
                "odds" => 8.5,
                "market_id" => "asd1237"
            ],
            [
                "odds" => 9.6,
                "market_id" => "asd1238"
            ],
        ];



        if($frame->data=='getUserSport') {
            wsEmit(["getUserSport" => ["sport_id" => 1]]);
        } else if($frame->data=='getAdditionalLeagues') {
            wsEmit(["getAdditionalLeagues" => $additionalLeagues]);
        } else if($frame->data=='getForRemovalLeagues') {
            wsEmit(["getForRemovalLeagues" => $removalLeagues]);
        } else if($frame->data=='getSelectedLeagues') {
            wsEmit(["getSelectedLeagues" => $selectedLeagues]);
        } else if($frame->data=='getEvents') {
            wsEmit(["getEvents" => $events]);
        } else if($frame->data=='getUpdatedOdds') {
            sleep(2);
            wsEmit(["getUpdatedOdds" => $updatedOdds]);
        } else if($frame->data=='getWatchlist') {
            wsEmit(["getWatchlist" => $watchlist]);
        }
    }

    public function onClose(Server $server, $fd, $reactorId)
    {
    }

    private function getUser($bearerToken)
    {
        $tokenguard = new TokenGuard(
            resolve(ResourceServer::class),
            Auth::createUserProvider('users'),
            resolve(TokenRepository::class),
            resolve(ClientRepository::class),
            resolve('encrypter')
        );
        $request = HttpRequest::create('/');
        $request->headers->set('Authorization', 'Bearer ' . $bearerToken);
        return $tokenguard->user($request);
    }
}
