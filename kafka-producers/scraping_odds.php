<?php

$conf = new RdKafka\Conf();
$conf->set('group.id', 'ml');
$conf->set('metadata.broker.list', 'kafka:9092');
$conf->set('auto.offset.reset', 'latest');
$conf->set('enable.auto.commit', 'false');

$rk    = new RdKafka\Producer($conf);
$topic = $rk->newTopic("SCRAPING-ODDS");

while (true) {
    try {
        $testData = [
            testData("Chile - Primera Division", "Union La Calera", "Coquimbo", "hg", 1, 'early', 1234567, "2020-03-18T01:00:00.000+04:00"),
//            testData("Spain Segunda Division", "Racing Santander", "CD Lugo", "hg", 1, 'early', 1234568, "2020-03-18T02:00:00.000+04:00"),
//            testData("League 1", "Home Team 1", "Away Team 1", "hg", 1, 'early', 1234569, "2020-03-18T02:00:00.000+04:00"),
//            testData("Chile - Primera Division", "Racing Santander", "Coquimbo", "hg", 1, 'today', 1234560, "2020-03-17T01:00:00.000+04:00"),
//            testData("Spain Segunda Division", "Union La Calera", "CD Lugo", "hg", 1, 'today', 1234561, "2020-03-17T02:00:00.000+04:00"),
//            testData("League 1", "Home Team 2", "Away Team 2", "hg", 1, 'today', 1234562, "2020-03-17T03:00:00.000+04:00"),
//            testData("League 1", "Home Team 3", "Away Team 3", "hg", 1, 'inplay', 1234563, "2020-03-17T16:00:00.000+04:00"),
//            testData("League 1", "Home Team 4", "Away Team 4", "hg", 1, 'inplay', 1234564, "2020-03-17T16:30:00.000+04:00"),
//            testData("Chile - Primera Division", "Home Team 1", "Away Team 1", "hg", 1, 'inplay', 1234565, "2020-03-17T15:30:00.000+04:00"),
//            testData("Chile - Primera Division", "Home Team 2", "Away Team 2", "hg", 1, 'inplay', 1234566, "2020-03-17T15:45:00.000+04:00"),
//
//            testData("Chile - Primera Division", "Home Team 3", "Away Team 3", "hg", 1, 'early', 1234577, "2020-03-17T15:45:00.000+04:00"),
//            testData("League 3", "Home Team 6", "Away Team 6", "hg", 1, 'early', 1234578, "2020-03-18T01:00:00.000+04:00"),
//            testData("League 4", "Home Team 5", "Away Team 5", "hg", 1, 'early', 1234579, "2020-03-18T01:00:00.000+04:00"),
//            testData("League 5", "Home Team 7", "Away Team 7", "hg", 1, 'early', 1234580, "2020-03-18T01:00:00.000+04:00"),
//            testData("League 6", "Home Team 8", "Away Team 8", "hg", 1, 'early', 1234581, "2020-03-18T01:00:00.000+04:00"),
//            testData("League 7", "Home Team 9", "Away Team 9", "hg", 1, 'early', 1234582, "2020-03-18T01:00:00.000+04:00"),

            //PIN
//            testData("Chile - Primera Division", "Union La Calera", "Coquimbo", "pin", 1, 'early', 2234567, "2020-03-18T01:00:00.000+04:00"),
//            testData("Spain Segunda Division", "Racing Santander", "CD Lugo", "pin", 1, 'early', 2234568, "2020-03-18T02:00:00.000+04:00"),
//            testData("League 1", "Home Team 1", "Away Team 1", "pin", 1, 'early', 2234569, "2020-03-18T02:00:00.000+04:00"),
//            testData("Chile - Primera Division", "Racing Santander", "Coquimbo", "pin", 1, 'today', 2234560, "2020-03-17T01:00:00.000+04:00"),
//            testData("Spain Segunda Division", "Union La Calera", "CD Lugo", "pin", 1, 'today', 2234561, "2020-03-17T02:00:00.000+04:00"),
//            testData("League 1", "Home Team 2", "Away Team 2", "pin", 1, 'today', 2234562, "2020-03-17T03:00:00.000+04:00"),
//            testData("League 1", "Home Team 3", "Away Team 3", "pin", 1, 'inplay', 2234563, "2020-03-17T16:00:00.000+04:00"),
//            testData("League 1", "Home Team 4", "Away Team 4", "pin", 1, 'inplay', 2234564, "2020-03-17T16:30:00.000+04:00"),
//            testData("Chile - Primera Division", "Home Team 1", "Away Team 1", "pin", 1, 'inplay', 2234565, "2020-03-17T15:30:00.000+04:00"),
//
//            testData("Chile - Primera Division", "Home Team 4", "Away Team 4", "pin", 1, 'early', 2234587, "2020-03-17T15:45:00.000+04:00"),
//            testData("League 2", "Home Team 2", "Away Team 2", "pin", 1, 'early', 2234588, "2020-03-17T15:45:00.000+04:00"),
//            testData("League 3", "Home Team 3", "Away Team 3", "pin", 1, 'early', 2234589, "2020-03-17T15:45:00.000+04:00"),
//            testData("League 3", "Home Team 5", "Away Team 5", "pin", 1, 'early', 2234590, "2020-03-17T15:45:00.000+04:00"),
//            testData("League 7", "Home Team 9", "Away Team 9", "pin", 1, 'early', 2234591, "2020-03-17T15:45:00.000+04:00"),
        ];
        echo '.';
        foreach ($testData as $data) {
            echo '*';
            $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($data));
        }
    } catch (\Exception $e) {
        echo '!';
    }
    usleep(5000000);
}


function testData($leagueName, $homeTeam, $awayTeam, $providerAlias, $sport, $schedule, $eventId, $refSchedule)
{
    $permittedChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    return [
        "request_uid" => 'str_shuffle($permittedChars)',
        "request_ts"  => milliseconds(),
        "command"     => "odd",
        "sub_command" => "transform",
        "data"        => [
            "provider"          => $providerAlias,
            "sport"             => $sport,
            "id"                => 1,
            "home_score"        => 0,
            "away_score"        => 0,
            "home_redcard"      => 0,
            "away_redcard"      => 0,
            "schedule"          => $schedule,
            "leagueName"        => $leagueName,
            "homeTeam"          => $homeTeam,
            "awayTeam"          => $awayTeam,
            "referenceSchedule" => $refSchedule,
            "runningtime"       => "2H 08:32",
            "events"            => [
                [
                    "eventId"     => $eventId,
                    "market_type" => 1,
                    "market_odds" => [
                        [
                            "oddsType"        => "1X2",
                            "marketSelection" => [
                                [
                                    "market_id" => "1X2H" . $eventId,
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ',')
                                ], [
                                    "market_id" => "1X2A" . $eventId,
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ',')
                                ], [
                                    "market_id" => "1X2D" . $eventId,
                                    "indicator" => "Draw",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ',')
                                ]
                            ]
                        ], [
                            "oddsType"        => "HDP",
                            "marketSelection" => [
                                [
                                    "market_id" => "",
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "-1.25"
                                ], [
                                    "market_id" => "",
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "+1.25"
                                ]
                            ]
                        ], [
                            "oddsType"        => "OU",
                            "marketSelection" => [
                                [
                                    "market_id" => "OUO" . $eventId,
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "O 2.5"
                                ], [
                                    "market_id" => "OUU" . $eventId,
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "U 2.5"
                                ]
                            ]
                        ], [
                            "oddsType"        => "HT 1X2",
                            "marketSelection" => [
                                [
                                    "market_id" => "H1X2H" . $eventId,
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ',')
                                ], [
                                    "market_id" => "H1X2A" . $eventId,
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ',')
                                ], [
                                    "market_id" => "H1X2D" . $eventId,
                                    "indicator" => "Draw",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ',')
                                ]
                            ]
                        ], [
                            "oddsType"        => "HT HDP",
                            "marketSelection" => [
                                [
                                    "market_id" => "HHDPH" . $eventId,
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "+0.5"
                                ], [
                                    "market_id" => "HHDPA" . $eventId,
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "-0.5"
                                ]
                            ]
                        ], [
                            "oddsType"        => "HT OU",
                            "marketSelection" => [
                                [
                                    "market_id" => "HOUO" . $eventId,
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "O 1.0"
                                ], [
                                    "market_id" => "HOUU" . $eventId,
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "U 1.0"
                                ]
                            ]
                        ], [
                            "oddsType"        => "OE",
                            "marketSelection" => [
                                [
                                    "market_id" => "OEH" . $eventId,
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "O 1.0"
                                ], [
                                    "market_id" => "OEA" . $eventId,
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "E 1.0"
                                ]
                            ]
                        ]
                    ]
                ], [
                    "eventId"     => (string) ((int) $eventId - 100),
                    "market_type" => 2,
                    "market_odds" => [
                        [
                            "oddsType"        => "HDP",
                            "marketSelection" => [
                                [
                                    "market_id" => "HDPH" . ((int) $eventId - 100),
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "-0.5"
                                ], [
                                    "market_id" => "HDPA" . ((int) $eventId - 100),
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "+0.5"
                                ]
                            ]
                        ], [
                            "oddsType"        => "HT HDP",
                            "marketSelection" => [
                                [
                                    "market_id" => "HHDPH" . ((int) $eventId - 100),
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "+1.0"
                                ], [
                                    "market_id" => "HHDPA" . ((int) $eventId - 100),
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "-1.0"
                                ]
                            ]
                        ], [
                            "oddsType"        => "OU",
                            "marketSelection" => [
                                [
                                    "market_id" => "OUO" . ((int) $eventId - 100),
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "O 3.5"
                                ], [
                                    "market_id" => "OUU" . ((int) $eventId - 100),
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "U 3.5"
                                ]
                            ]
                        ], [
                            "oddsType"        => "HT OU",
                            "marketSelection" => [
                                [
                                    "market_id" => "HOUO" . ((int) $eventId - 100),
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "O 0.5"
                                ], [
                                    "market_id" => "HOUU" . ((int) $eventId - 100),
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "U 0.5"
                                ]
                            ]
                        ], [
                            "oddsType"        => "1X2",
                            "marketSelection" => [
                                [
                                    "market_id" => "",
                                    "indicator" => "Home",
                                    "odds"      => ""
                                ], [
                                    "market_id" => "",
                                    "indicator" => "Away",
                                    "odds"      => ""
                                ], [
                                    "market_id" => "",
                                    "indicator" => "Draw",
                                    "odds"      => ""
                                ]
                            ]
                        ], [
                            "oddsType"        => "HT 1X2",
                            "marketSelection" => [
                                [
                                    "market_id" => "",
                                    "indicator" => "Home",
                                    "odds"      => ""
                                ], [
                                    "market_id" => "",
                                    "indicator" => "Away",
                                    "odds"      => ""
                                ], [
                                    "market_id" => "",
                                    "indicator" => "Draw",
                                    "odds"      => ""
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    "eventId"     => (string) ((int) $eventId - 200),
                    "market_type" => 2,
                    "market_odds" => [
                        [
                            "oddsType"        => "HDP",
                            "marketSelection" => [
                                [
                                    "market_id" => "HDPH" . ((int) $eventId - 200),
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "-0.75"
                                ], [
                                    "market_id" => "HDPA" . ((int) $eventId - 200),
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "+0.75"
                                ]
                            ]
                        ], [
                            "oddsType"        => "HT HDP",
                            "marketSelection" => [
                                [
                                    "market_id" => "HHDPH" . ((int) $eventId - 200),
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "+0.75"
                                ], [
                                    "market_id" => "HHDPA" . ((int) $eventId - 200),
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "-0.75"
                                ]
                            ]
                        ], [
                            "oddsType"        => "OU",
                            "marketSelection" => [
                                [
                                    "market_id" => "OUO" . ((int) $eventId - 200),
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "O 3.0"
                                ], ["market_id" => "OUU" . ((int) $eventId - 200),
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "U 3.0"
                                ]
                            ]
                        ], [
                            "oddsType"        => "HT OU",
                            "marketSelection" => [
                                [
                                    "market_id" => "HOUO" . ((int) $eventId - 200),
                                    "indicator" => "Home",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "O 0.75"
                                ], [
                                    "market_id" => "HOUU" . ((int) $eventId - 200),
                                    "indicator" => "Away",
                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
                                    "points"    => "U 0.75"
                                ]
                            ]
                        ], [
                            "oddsType"        => "1X2",
                            "marketSelection" => [
                                [
                                    "market_id" => "",
                                    "indicator" => "Home",
                                    "odds"      => ""
                                ], [
                                    "market_id" => "",
                                    "indicator" => "Away",
                                    "odds"      => ""
                                ], [
                                    "market_id" => "",
                                    "indicator" => "Draw",
                                    "odds"      => ""
                                ]
                            ]
                        ], [
                            "oddsType"        => "HT 1X2",
                            "marketSelection" => [
                                [
                                    "market_id" => "",
                                    "indicator" => "Home",
                                    "odds"      => ""
                                ], [
                                    "market_id" => "",
                                    "indicator" => "Away",
                                    "odds"      => ""
                                ], [
                                    "market_id" => "",
                                    "indicator" => "Draw",
                                    "odds"      => ""
                                ]
                            ]
                        ]
                    ]
                ], //[
//                    "eventId"     => (string) ((int) $eventId - 300),
//                    "market_type" => 2,
//                    "market_odds" => [
//                        [
//                            "oddsType"        => "HDP",
//                            "marketSelection" => [
//                                [
//                                    "market_id" => "HDPH" . ((int) $eventId - 300),
//                                    "indicator" => "Home",
//                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
//                                    "points"    => "-3.75"
//                                ], [
//                                    "market_id" => "HDPA" . ((int) $eventId - 300),
//                                    "indicator" => "Away",
//                                    "odds"      => number_format(rand(80, 300) / 100, 2, '.', ','),
//                                    "points"    => "+3.75"
//                                ]
//                            ]
//                        ], [
//                            "oddsType"        => "HT HDP",
//                            "marketSelection" => [
//                                [
//                                    "market_id" => "HHDPH" . ((int) $eventId - 300),
//                                    "indicator" => "Home",
//                                    "odds"      => "1.38",
//                                    "points"    => "+3.75"
//                                ], [
//                                    "market_id" => "HHDPA" . ((int) $eventId - 300),
//                                    "indicator" => "Away",
//                                    "odds"      => "0.571",
//                                    "points"    => "-3.75"
//                                ]
//                            ]
//                        ], [
//                            "oddsType"        => "OU",
//                            "marketSelection" => [
//                                [
//                                    "market_id" => "OUO" . ((int) $eventId - 300),
//                                    "indicator" => "Home",
//                                    "odds"      => "1.82",
//                                    "points"    => "O 6.0"
//                                ], ["market_id" => "OUU" . ((int) $eventId - 300),
//                                    "indicator" => "Away",
//                                    "odds"      => "0.404",
//                                    "points"    => "U 6.0"
//                                ]
//                            ]
//                        ], [
//                            "oddsType"        => "HT OU",
//                            "marketSelection" => [
//                                [
//                                    "market_id" => "HOUO" . ((int) $eventId - 300),
//                                    "indicator" => "Home",
//                                    "odds"      => "0.571",
//                                    "points"    => "O 3.75"
//                                ], [
//                                    "market_id" => "HOUU" . ((int) $eventId - 300),
//                                    "indicator" => "Away",
//                                    "odds"      => "1.34",
//                                    "points"    => "U 3.75"
//                                ]
//                            ]
//                        ], [
//                            "oddsType"        => "1X2",
//                            "marketSelection" => [
//                                [
//                                    "market_id" => "",
//                                    "indicator" => "Home",
//                                    "odds"      => ""
//                                ], [
//                                    "market_id" => "",
//                                    "indicator" => "Away",
//                                    "odds"      => ""
//                                ], [
//                                    "market_id" => "",
//                                    "indicator" => "Draw",
//                                    "odds"      => ""
//                                ]
//                            ]
//                        ], [
//                            "oddsType"        => "HT 1X2",
//                            "marketSelection" => [
//                                [
//                                    "market_id" => "",
//                                    "indicator" => "Home",
//                                    "odds"      => ""
//                                ], [
//                                    "market_id" => "",
//                                    "indicator" => "Away",
//                                    "odds"      => ""
//                                ], [
//                                    "market_id" => "",
//                                    "indicator" => "Draw",
//                                    "odds"      => ""
//                                ]
//                            ]
//                        ]
//                    ]
//                ]
            ]
        ]
    ];
}

function milliseconds()
{
    $mt = explode(' ', microtime());
    return bcadd($mt[1], $mt[0], 8);
}

