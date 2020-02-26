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
        $this->message = json_decode($message->payload);
    }

    public function handle()
    {
        $toInsert    = [];
        $toTransform = true;
        $updated     = false;

        $swoole         = app('swoole');
//        $indexesTable   = $swoole->indexesTable;

        /** DATABASE TABLES */
        /** LOOK-UP TABLES */
        $providersTable       = $swoole->providersTable;
        $sportsTable          = $swoole->sportsTable;
        $rawLeaguesTable      = $swoole->rawLeaguesTable;
        $leaguesTable         = $swoole->leaguesTable;
        $rawTeamsTable        = $swoole->rawTeamsTable;
        $teamsTable           = $swoole->teamsTable;
        $rawEventsTable       = $swoole->rawEventsTable;
        $eventsTable          = $swoole->eventsTable;
        $oddTypesTable        = $swoole->oddTypesTable;
        $sportOddTypesTable   = $swoole->sportOddTypesTable;
        $rawEventMarketsTable = $swoole->rawEventMarketsTable;
        $eventMarketsTable    = $swoole->eventMarketsTable;
        $transformedTable     = $swoole->transformedTable;

        $arrayOddTypes = [];

        /**
         * PROVIDERS Swoole Table
         *
         * @ref config.laravels.providers
         *
         * @var $providersTable  swoole_table
         *      $providerSwtId   swoole_table_key    "providerAlias:<strtolower($provider)>"
         *      $providerId      swoole_table_value  int
         */
        $providerSwtId = "providerAlias:" . strtolower($this->message->data->provider);

        if ($providersTable->exist($providerSwtId)) {
            $providerId = $providersTable->get($providerSwtId)['id'];
        } else {
            throw new Exception("Provider doesn't exist");
        }

        /**
         * SPORTS Swoole Table
         *
         * @ref config.laravels.sports
         *
         * @var $sportsTable  swoole_table
         *      $sportSwtId   swoole_table_key    "sId:<$sportId>"
         *      $sportId      swoole_table_value  int
         */
        $sportSwtId = "sId:" . $this->message->data->sportId;

        if ($sportsTable->exists($sportSwtId)) {
            $sportId   = $sportsTable->get($sportSwtId)['id'];
            $sportName = $sportsTable->get($sportSwtId)['sport'];
        } else {
            throw new Exception("Sport doesn't exist");
        }

        /**
         * LEAGUES (RAW) Swoole Table
         *
         * @ref config.laravels.rawLeagues
         *
         * @var $rawLeaguesTable  swoole_table
         *      $rawLeagueSwtId   swoole_table_key    "sId:<$sportId>:pId:<$providerId>:league:<slug($leagueName)>"
         *      $rawLeagueId      swoole_table_value  int
         */
        $rawLeagueSwtId = implode(':', [
            "sId:"      . $sportId,
            "provider:" . $providerId,
            "league:"   . Str::slug($this->message->data->leagueName)
        ]);

        if ($rawLeaguesTable->exist($rawLeagueSwtId)) {
            $rawLeagueId = $rawLeaguesTable->get($rawLeagueSwtId)['id'];
        } else {
            /** TODO: Insert to DB */
            $toInsert['raw_leagues'] = [];
            $toTransform             = false;
        }

        /**
         * LEAGUES (MASTER) Swoole Table
         *
         * @ref config.laravels.leagues
         *
         * @var $leaguesTable    swoole_table
         *      $leagueSwtId     swoole_table_key    "sId:<$sportId>:pId:<$providerId>:lId:<$rawLeagueId>"
         *      $multiLeagueId   swoole_table_value  int
         */
        $leagueSwtId = implode(':', [
            "sId:" . $sportId,
            "pId:" . $providerId,
            "lId:" . $rawLeagueId
        ]);

        if ($leaguesTable->exists($leagueSwtId)) {
            $multiLeagueId   = $leaguesTable->get($leagueSwtId)['id'];
            $multiLeagueName = $leaguesTable->get($leagueSwtId)['multi_league'];
        } else {
            /** TODO: Insert to DB */
            $toInsert['master_leagues'] = [];
            $toTransform = false;
        }

        /**
         * TEAMS (RAW) Swoole Table
         *
         * @ref config.laravels.rawTeams
         *
         * @var $competitors    array               Contains both `HOME` and `AWAY` team names
         *      $rawTeamsTable  swoole_table
         *      $rawTeamSwtId   swoole_table_key    "pId:<$providerId>:team:<slug($homeTeam|$awayTeam)>"
         *      $rawTeamName    swoole_table_value  string
         */
        $multiTeam = [];
        $competitors = [
            'home' => $this->message->data->homeTeam,
            'away' => $this->message->data->awayTeam,
        ];

        foreach ($competitors AS $key => $row) {
            $rawTeamSwtId = implode(':', [
                "pId:"   . $providerId,
                "teams:" . Str::slug($competitors[$key])
            ]);

            if ($rawTeamsTable->exists($rawTeamSwtId)) {
                $rawTeamName = $rawTeamsTable->get($rawTeamSwtId)['team'];
            } else {
                /** TODO: Insert to DB */
                $toInsert['raw_teams'][] = [];
                $toTransform             = false;
            }

            /**
             * TEAMS (MASTER) Swoole Table
             *
             * @ref config.laravels.teams
             *
             * @var $teamsTable  swoole_table
             *      $teamSwtId   swoole_table_key    "pId:<$providerId>:team:<slug($rawTeamName)>"
             */
            $teamSwtId = implode(':', [
                "pId:"       . $providerId,
                "team:" . Str::slug($rawTeamName)
            ]);

            if ($teamsTable->exists($teamSwtId)) {
                $multiTeam[$key] = $teamsTable->get($teamSwtId)['multi_team'];
            } else {
                /** TODO: Insert to DB */
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
         *      $rawEventSwtId   swoole_table_key    "pId:<$providerId>:lId:<$rawLeagueId>:eventIdentifier:<$events[]->eventId>"
         *      $rawEventId      swoole_table_value  int
         */
        $rawEventSwtId = implode(':', [
            "pId:" . $providerId,
            "lId:" . $rawLeagueId,
            "eventIdentifier:" . $this->message->data->events[0]->eventId
        ]);

        if ($rawEventsTable->exists($rawEventSwtId)) {
            $rawEventId = $rawEventsTable->get($rawEventSwtId)['id'];
        } else {
            /** TODO: Insert to DB */
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
         *      $eventSwtId   swoole_table_key    "sId:<$sportId>:pId:<$providerId>:eId:<$rawEventId>"
         *      $event        swoole_table_value  string
         */
        $eventSwtId = implode(':', [
            "sId:" . $sportId,
            "pId:" . $providerId,
            "eId:" . $rawEventId
        ]);

        if ($eventsTable->exists($eventSwtId)) {
            $eventId = $eventsTable->get($eventSwtId)['id'];
            $uid     = $eventsTable->get($eventSwtId)['master_event_unique_id'];
        } else {
            $uid = implode('-', [
                date("Ymd", strtotime($this->message->data->referenceSchedule)),
                $sportId,
                $multiLeagueId,
                $this->message->data->events[0]->eventId
            ]);

            /** TODO: Insert to DB */
            $toInsert['master_events'] = [];
            $toTransform               = false;

            //@TODO Insert new DB data to SWT
        }

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
                 *      $sportOddTypeSwtId   swoole_table_key    "sId:<$sportId>:oddTypeId:<$oddTypeId>"
                 *      $sportOddTypeId      swoole_table_value  int
                 */
                $sportOddTypeSwtId = implode(':', [
                    "sId:"       . $sportId,
                    "oddTypeId:" . $oddTypeId
                ]);

                if (!$sportOddTypesTable->exist($sportOddTypeSwtId)) {
                    throw new Exception("Sport Odds Type doesn't exist");
                }

                $arrayOddTypes[] = $columns->oddsType;

                /** loop each `marketSelection` from each `market_odds` */
                foreach ($columns->marketSelection AS $markets) {
                    /**
                     * MARKETS (RAW) Swoole Table
                     *
                     * @ref config.laravels.rawEventMarkets
                     *
                     * @var $rawEventMarketsTable  swoole_table
                     *      $rawEventMarketSwtId   swoole_table_key    "lId:<$rawLeagueId>:pId:<$providerId>:eId:<$events[]->eventId>"
                     *      $rawEventMarket        swoole_table_value  string
                     */
                    $rawEventMarketSwtId = implode(':', [
                        "lId:" . $rawLeagueId,
                        "pId:" . $providerId,
                        "eId:" . $rawEventId
                    ]);

                    if ($rawEventMarketsTable->exists($rawEventMarketSwtId)) {
                        $rawEventMarketId   = $rawEventMarketsTable->get($rawEventMarketSwtId)['id'];
                        $rawEventMarketOdds = $rawEventMarketsTable->get($rawEventMarketSwtId)['odds'];
                    } else {
                        /** TODO: Insert to DB */
                        $toInsert['raw_event_markets'] = [];
                        $toTransform                   = false;
                    }

                    /**
                     * EVENT MARKETS (MASTER) Swoole Table
                     *
                     * @ref config.laravels.eventMarkets
                     *
                     * @var $eventMarketsTable  swoole_table
                     *      $eventMarketSwtId   swoole_table_key    "pId:<$providerId>:meUniqueId:<$masterEventUniqueId>:memUniqueId:<$masterEventMarketUniqueId>"
                     *      $eventMarket        swoole_table_value  string
                     */
                    $found = false;

                    foreach ($eventMarketsTable AS $key => $row) {
                        if (strpos($key, 'pId:' . $providerId . ':meUniqueId:' . $uid . ':memUniqueId:') == 0) {
                            if ($row['event_market_id'] == $rawEventMarketId) {
                                $found = true;

                                if ($row['odds'] != $rawEventMarketOdds) {
                                    $eventMarketsTable[$key]['odds'] = $rawEventMarketOdds;
                                    $updated                         = true;
                                }

                                break;
                            }
                        }
                    }

                    if (!$found) {
                        $memUID                    = uniqid();
                        $toInsert['event_markets'] = [];
                        $toTransform               = false;

                        $eventMarketSwtId = implode(':', [
                            "pId:"         . $providerId,
                            "meUniqueId:"  . $uid,
                            "memUniqueId:" . $memUID
                        ]);

                        /** TODO: Insert to DB */

//                        $eventMarketsTable->set($eventMarketSwtId, [
//                            'id'                            => null, // $id
//                            'event_id'                      => $eventId,
//                            'odd_type_id'                   => $oddTypeId,
//                            'master_event_market_unique_id' => $memUID,
//                            'master_event_unique_id'        => $uid,
//                            'event_market_id'               => $rawEventMarketId,
//                            'provider_id'                   => $providerId,
//                            'odds'                          => $markets->odds,
//                            'odd_label'                     => array_key_exists('points', $markets) ? $markets->points : "",
//                            'bet_identifier'                => $markets->market_id,
//                            'is_main'                       => $event->market_type == 1 ? true : false,
//                            'market_flag'                   => strtoupper($markets->indicator),
//                        ];
                    }
                }
            }
        }

        /** Data Transformation */
        if ($toTransform && !$updated) {
            $transformedJSON = [
                'uid'           => $uid,
                'sport_id'      => $sportId,
                'sport'         => $sportId,
                'provider_id'   => $providerId,
                'game_schedule' => $this->message->data->type,
                'league_name'   => $multiLeagueName,
                'home'          => [
                    'name'    => $multiTeam['home'],
                    'score'   => $this->message->data->home_score,
                    'redcard' => $this->message->data->home_redcard
                ],
                'away'          => [
                    'name'    => $multiTeam['away'],
                    'score'   => $this->message->data->away_score,
                    'redcard' => $this->message->data->away_redcard
                ],
                'ref_schedule'  => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                'running_time'  => $this->message->data->running_time,
                'market_odds'   => [],
            ];

            /** Forming Main Markets */
            foreach ($this->message->data->events[0]->market_odds AS $columns) {
                if (in_array($columns->oddsType, $arrayOddTypes)) {
                    foreach ($columns->marketSelections AS $_market) {
                        $_marketOdds   = $_market->odds;

                        $transformedJSON['market_odds']['main'][$columns->oddsType][strtolower($_market->indicator)] = [
                            'odds'      => (float) $_marketOdds,
                            'market_id' => $_market->market_id
                        ];

                        if (array_key_exists('points', $_market)) {
                            $transformedJSON['market_odds']['main'][$columns->oddsType][strtolower($_market->indicator)]['points'] = $_market->points;
                        }
                    }
                }
            }

            /** Forming Other Markets */
            $i = 0;

            foreach ($this->message->data->events[1]->market_odds AS $columns) {
                if (in_array($columns->oddsType, $arrayOddTypes)) {
                    foreach ($columns->marketSelections AS $_market) {
                        $_marketOdds   = $_market->odds;

                        $transformedJSON['market_odds']['other'][$columns->oddsType][strtolower($_market->indicator)] = [
                            'odds'      => (float) $_marketOdds,
                            'market_id' => $_market->market_id
                        ];

                        if (array_key_exists('points', $_market)) {
                            $transformedJSON['market_odds']['other'][$i][$columns->oddsType][strtolower($_market->indicator)]['points'] = $_market->points;
                        }
                    }

                    $i++;
                }
            }

            $transformedSwtId = "uid:" . $uid;

            if (!$transformedTable->exists($transformedSwtId)) {
                $transformedTable->set($transformedSwtId, json_encode($transformedJSON));
            }
        }
    }
}
