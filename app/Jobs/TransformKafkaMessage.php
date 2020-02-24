<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\{Events, League, Provider, Teams};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DateTime;
use Exception;

class TransformKafkaMessage implements ShouldQueue
{
    use Dispatchable;

    protected $message;
    protected $swoole;

    public function __construct($message)
    {
        $this->message = json_decode($message);
    }

    public function handle()
    {
        $toInsert = [];
        $toTransform = true;

        //@TODO Transformation
        $swoole         = app('swoole');
        $indexesTable   = $swoole->indexesTable;

        /** DATABASE TABLES */
        /** LOOK-UP TABLES */
        $providersTable = $swoole->providersTable;
        $sportsTable    = $swoole->sportsTable;
        $leaguesTable   = $swoole->leaguesTable;
        $eventsTable    = $swoole->eventsTable;
        $teamsTable     = $swoole->teamsTable;
        $marketsTable   = $swoole->marketsTable;
        /** TODO: UPDATE COMPLETE LISTS OF SWOOLE TABLES TO BE USED */

        /**
         * PROVIDERS Swoole Table
         *
         * @ref config.laravels.providers
         * @var $providersTable  swoole_table
         *      $providerSwtId   swoole_table_key    "provider:<strtolower($provider)>"
         *      $provider        swoole_table_value  string
         */
        $providerSwtId = "provider:" . strtolower($this->message->data->provider);

        if ($providersTable->exist($providerSwtId)) {
            $provider = strtolower($providersTable->get($providerSwtId)['alias']);
        } else {
            throw new Exception("Provider doesn't exist");
        }

        /**
         * SPORTS Swoole Table
         *
         * @ref config.laravels.sports
         * @var $sportsTable  swoole_table
         *      $sportSwtId   swoole_table_key    "sport:<$sportId>"
         *      $sportId      swoole_table_value  int
         */
        $sportSwtId = "sport:" . $this->message->data->sportId;

        if ($sportsTable->exists($sportSwtId)) {
            $sportId = $sportsTable->get($sportSwtId)['id'];
        } else {
            throw new Exception("Sport doesn't exist");
        }

        /**
         * LEAGUES (RAW) Swoole Table
         *
         * @ref config.laravels.rawLeagues
         *
         * @var $rawLeaguesTable  swoole_table
         *      $rawLeagueSwtId   swoole_table_key    "sport:<$sportId>,provider:<strtolower($provider)>,league:<slug($leagueName)>"
         *      $rawLeagueName    swoole_table_value  string
         */
        $rawLeagueSwtId = implode(':', [
            "sport:"    . $sportId,
            "provider:" . $provider,
            "league:"   . Str::slug($this->message->data->leagueName)
        ]);

        if ($rawLeaguesTable->exist($rawLeagueSwtId)) {
            $rawLeagueName = $rawLeaguesTable->get($rawLeagueSwtId)['league'];
        } else {
            /** TO INSERT */
            $toInsert['raw_leagues'] = [];
            $toTransform             = false;
        }

        /**
         * LEAGUES (MASTER) Swoole Table
         *
         * @ref config.laravels.leagues
         *
         * @var $leaguesTable  swoole_table
         *      $leagueSwtId   swoole_table_key    "sport:<$sportId>,provider:<strtolower($provider)>,league:<slug($leagueName)>"
         *      $multiLeague   swoole_table_value  string
         */
        // TODO: LEAGUES (MASTER) LOGIC

        /**
         * TEAMS (RAW) Swoole Table
         *
         * @ref config.laravels.rawTeams
         *
         * @var $competitors    array               Contains both `HOME` and `AWAY` team names
         *      $rawTeamsTable  swoole_table
         *      $rawTeamSwtId   swoole_table_key    "provider:<strtolower($provider)>,team:<slug($homeTeam|$awayTeam)>"
         *      $rawTeamName    swoole_table_value  string
         */
        $competitors = [
            'home' => $this->message->data->homeTeam,
            'away' => $this->message->data->awayTeam,
        ];

        foreach ($competitors AS $key => $row) {
            $rawTeamSwtId = implode(',', [
                "provider:" . $provider,
                "teams:"    . Str::slug($competitors[$key])
            ]);

            if ($rawTeamsTable->exists($rawTeamSwtId)) {
                $rawTeamName = $rawTeamstable->get($rawTeamSwtId)['team'];
            } else {
                /** TO INSERT */
                $toInsert['raw_teams'][] = [];
                $toTransform             = false;
            }

            /**
             * TEAMS (MASTER) Swoole Table
             *
             * @ref config.laravels.teams
             *
             * @var $teamsTable  swoole_table
             *      $teamSwtId   swoole_table_key    "multiTeam:<slug($rawTeamName)>"
             */
            $teamSwtId = "multiTeam:" . Str::slug($rawTeamName);

            if (!$teamsTable->exists($teamSwtId)) {
                /** TO INSERT */
                $toInsert['master_teams'][] = [];
                $toTransform                = false;
            }
        }

        /**
         * EVENTS (RAW) Swoole Table
         *
         * @ref config.laravels.rawEvents
         *
         * @var $rawEventsTable  swoole_table
         *      $rawEventSwtId   swoole_table_key    "sport:<$sportId>,provider:<strtolower($provider)>,league:<slug($rawLeagueName)>,eventIdentifier:<$events[]->eventId>"
         *      $rawEvent        swoole_table_value  string
         */
        $rawEventSwtId = implode(',', [
            "sport:"           . $sportId,
            "provider:"        . $provider,
            "league:"          . $rawLeagueName,
            "eventIdentifier:" . $this->message->data->events[0]->eventId
        ]);

        if ($rawEventsTable->exists($rawEventSwtId)) {
            // TODO: EVENTS (RAW) LOGIC
        } else {
            /** TO INSERT */
            $toInsert['raw_events'] = [];
            $toTransform            = false;
        }

        /**
         * EVENTS (MASTER) Swoole Table
         *
         * @ref config.laravels.events
         *
         * @var $arrayEvents  array               Contains Event information extracted from game data json
         *      $eventsTable  swoole_table
         *      $eventSwtId   swoole_table_key    ""
         *      $event        swoole_table_value  string
         */
        // TODO: EVENTS (MASTER) LOGIC

        /** `events` key from json data */
        $arrayEvents = $this->message->data->events;

        /** loop each `events` */
        foreach ($arrayEvents AS $event) {
            /** loop each `market_odds` inside every `events` */
            foreach ($event->market_odds AS $columns) {
                /**
                 * ODD TYPES Swoole Table
                 *
                 * @ref config.laravels.oddType
                 *
                 * @var $oddTypesTable  swoole_table
                 *      $oddTypeSwtId   swoole_table_key    "oddType:<$events[]->market_odds[]->oddsType>"
                 *      $oddTypeId      swoole_table_value  int
                 */
                $oddTypeSwtId = "oddType:" . $columns->oddsType;

                if ($oddTypesTable->exists($oddTypeSwtId)) {
                    $oddTypeId = $oddTypesTable->get($oddTypeSwtId)['id'];
                } else {
                    throw new Exception("Odds Type doesn't exist");
                }

                /**
                 * SPORT ODD TYPES Swoole Table
                 *
                 * @ref config.laravels.sportOddType
                 *
                 * @var $sportOddTypesTable  swoole_table
                 *      $sportOddTypeSwtId   swoole_table_key    "sport:<$sportId>,oddTypeId:<$oddTypeId>"
                 *      $sportOddTypeId      swoole_table_value  int
                 */
                $sportOddTypeSwtId = implode(',', [
                    "sport:"     . $sportId,
                    "oddTypeId:" . $oddTypeId
                ]);

                if (!$sportOddTypesTable->exist($sportOddTypeSwtId)) {
                    throw new Exception("Sport Odds Type doesn't exist");
                }

                /** loop each `marketSelection` from each `market_odds` */
                foreach ($columns->marketSelection AS $markets) {
                    /**
                     * MARKETS (RAW) Swoole Table
                     *
                     * @ref config.laravels.rawEventMarkets
                     *
                     * @var $rawEventMarketsTable  swoole_table
                     *      $rawEventMarketSwtId   swoole_table_key    "sport:<$sportId>,leagueName:<slug($leagueName)>,eventIdentifier:<$events[]->eventId>"
                     *      $rawEventMarket        swoole_table_value  string|hash
                     */
                    $rawEventMarketSwtId = implode(':', []);

                    if ($rawEventMarketsTable->exists($rawEventMarketSwtId)) {
                        /** TODO: EVENT MARKET ODDS (RAW) LOGIC */
                    } else {
                        /** TO INSERT */
                        $toInsert['raw_event_markets'] = [];
                        $toTransform                   = false;
                    }

                    /**
                     * EVENT MARKETS (MASTER) Swoole Table
                     *
                     * @ref config.laravels.eventMarkets
                     *
                     * @var $eventMarketsTable  swoole_table
                     *      $eventMarketSwtId   swoole_table_key    "sport:<$sportId>,masterLeagueNameId:<$masterLeagueNameId>,masterEventIdentifier:<$masterEventIdentifierId>"
                     *      $eventMarket        swoole_table_value  string|hash
                     */
                    $eventMarketSwtId = implode(':', [
                        "sport:"                 . $sportId,
                        "masterLeagueName:"      . $masterLeagueNameId,
                        "masterEventIdentifier:" . $masterEventIdentifierId
                    ]);

                    if ($eventMarketsTable->exists($eventMarketSwtId)) {
                        /** TODO: EVENT MARKET ODDS (MASTER) LOGIC */
                    } else {
                        /** TO INSERT */
                        $toInsert['event_markets'] = [];
                        $toTransform               = false;
                    }
                }
            }
        }

        /** Data Insertions */
        if (!is_null($toInsert)) {
            /** TODO: Data Insertions */
        }

        /** Data Transformation */
        if ($toTransform) {
            /** TODO: TRANSFORMATION */
            /**
                GOAL JSON FORMAT (SOCCER)
                [
                    'requested_uid' => 123456789,
                    'requested_ts'  => 987654321,
                    'provider_id'   => 1,
                    'event_id'      => "qwe123",
                    'game_schedule' => "early",
                    'league_name'   => "Australia Tasmania Summer Cup",
                    'home'          => [
                        'name'    => "Glenorchy Knights",
                        'score'   => 1,
                        'redcard' => 0
                    ],
                    'away'          => [
                        'name'    => "Kingborough Lions United",
                        'score'   => 0,
                        'redcard' => 1
                    ],
                    'ref_schedule'  => "2020-02-13 08:00:00",
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
                                    'odds'      => "O 1.23",
                                    'market_id' => "asd123"
                                ],
                                'away' => [
                                    'odds'      => "E 1.23",
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
                ];
            **/
        }
    }
}