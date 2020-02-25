<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WsEvents implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $params)
    {
        $this->userId = $userId;
        $this->multi_league = $params[1];
    }

    public function handle()
    {
        $server = app('swoole');
        $fd = $server->wsTable->get('uid:' . $this->userId);

        $getEvents = [];

        $eventData = $this->sampleOutput();var_dump($eventData);
        foreach ($eventData as $jsonData) {
            $data = json_decode($jsonData, true);
            unset($data['market_odds']['other']);
            $getEvents[] = $data;
        }

        $server->push($fd['value'], json_encode([
            'getEvents' => $getEvents
        ]));
    }

    private function sampleOutput() {
        return [json_encode([
            'uid'           => 'asdasd',
            'sport_id'      => 1,
            'sport'         => "Soccer",
            'provider_id'   => 1,
            'game_schedule' => "inplay",
            'league_name'   => "English Football League",
            'home'          => [
                'name'    => "Glenorchy Knights",
                'score'   => 0,
                'redcard' => 0
            ],
            'away'          => [
                'name'    => "Kingborough Lions United",
                'score'   => 0,
                'redcard' => 1
            ],
            'ref_schedule'  => "2020-02-13 08:00:00",
            'running_time'  => "2H 20:58",
            'market_odds'   => [
                'main'  => [
                    '1X2'    => [
                        'home' => [
                            'odds'      => 1.23,
                            'market_id' => "asd123"
                        ],
                        'away' => [
                            'odds'      => 1.23,
                            'market_id' => "asd123"
                        ],
                        'draw' => [
                            'odds'      => 1.23,
                            'market_id' => "asd123"
                        ],
                    ],
                    'HDP'    => [
                        'home' => [
                            'odds'      => 1.23,
                            'points'    => '-2.5',
                            'market_id' => "asd123"
                        ],
                        'away' => [
                            'odds'      => 1.23,
                            'points'    => '+2.5',
                            'market_id' => "asd123"
                        ],
                    ],
                    'OU'     => [
                        'home' => [
                            'odds'      => 1.23,
                            'points'    => 'O 2.5',
                            'market_id' => "asd123"
                        ],
                        'away' => [
                            'odds'      => 1.23,
                            'points'    => 'U 2.5',
                            'market_id' => "asd123"
                        ],
                    ],
                    'OE'     => [
                        'home' => [
                            'odds'      => "1.23",
                            'points'    => "O",
                            'market_id' => "asd123"
                        ],
                        'away' => [
                            'odds'      => "1.23",
                            'points'    => "E",
                            'market_id' => "asd123"
                        ],
                    ],
                    'HT 1X2' => [
                        'home' => [
                            'odds'      => 1.23,
                            'market_id' => "asd123"
                        ],
                        'away' => [
                            'odds'      => 1.23,
                            'market_id' => "asd123"
                        ],
                        'draw' => [
                            'odds'      => 1.23,
                            'market_id' => "asd123"
                        ],
                    ],
                    'HT HDP' => [
                        'home' => [
                            'odds'      => 1.23,
                            'points'    => '-2.5',
                            'market_id' => "asd123"
                        ],
                        'away' => [
                            'odds'      => 1.23,
                            'points'    => '+2.5',
                            'market_id' => "asd123"
                        ],
                    ],
                    'HT OU'  => [
                        'home' => [
                            'odds'      => 1.23,
                            'points'    => 'O 2.5',
                            'market_id' => "asd123"
                        ],
                        'away' => [
                            'odds'      => 1.23,
                            'points'    => 'U 2.5',
                            'market_id' => "asd123"
                        ],
                    ],
                ],
                'other' => [
                    [
                        '1X2'    => [],
                        'HDP'    => [
                            'home' => [
                                'odds'      => 1.23,
                                'points'    => '-1.5',
                                'market_id' => "asd123"
                            ],
                            'away' => [
                                'odds'      => 1.23,
                                'points'    => '+1.5',
                                'market_id' => "asd123"
                            ],
                        ],
                        'OU'     => [
                            'home' => [
                                'odds'      => 1.23,
                                'points'    => 'O 1.5',
                                'market_id' => "asd123"
                            ],
                            'away' => [
                                'odds'      => 1.23,
                                'points'    => 'U 1.5',
                                'market_id' => "asd123"
                            ],
                        ],
                        'OE'     => [],
                        'HT 1X2' => [],
                        'HT HDP' => [
                            'home' => [
                                'odds'      => 1.23,
                                'points'    => '-1.5',
                                'market_id' => "asd123"
                            ],
                            'away' => [
                                'odds'      => 1.23,
                                'points'    => '+1.5',
                                'market_id' => "asd123"
                            ],
                        ],
                        'HT OU'  => [
                            'home' => [
                                'odds'      => 1.23,
                                'points'    => 'O 1.5',
                                'market_id' => "asd123"
                            ],
                            'away' => [
                                'odds'      => 1.23,
                                'points'    => 'U 1.5',
                                'market_id' => "asd123"
                            ],
                        ],
                    ],
                    [
                        '1X2'    => [],
                        'HDP'    => [
                            'home' => [
                                'odds'      => 1.23,
                                'points'    => '-0.5',
                                'market_id' => "asd123"
                            ],
                            'away' => [
                                'odds'      => 1.23,
                                'points'    => '+0.5',
                                'market_id' => "asd123"
                            ],
                        ],
                        'OU'     => [
                            'home' => [
                                'odds'      => 1.23,
                                'points'    => 'O 0.5',
                                'market_id' => "asd123"
                            ],
                            'away' => [
                                'odds'      => 1.23,
                                'points'    => 'U 0.5',
                                'market_id' => "asd123"
                            ],
                        ],
                        'OE'     => [],
                        'HT 1X2' => [],
                        'HT HDP' => [
                            'home' => [
                                'odds'      => 1.23,
                                'points'    => '-0.5',
                                'market_id' => "asd123"
                            ],
                            'away' => [
                                'odds'      => 1.23,
                                'points'    => '+0.5',
                                'market_id' => "asd123"
                            ],
                        ],
                        'HT OU'  => [
                            'home' => [
                                'odds'      => 1.23,
                                'points'    => 'O 0.5',
                                'market_id' => "asd123"
                            ],
                            'away' => [
                                'odds'      => 1.23,
                                'points'    => 'U 0.5',
                                'market_id' => "asd123"
                            ],
                        ],
                    ],
                ],
            ],
        ])];
    }
}
