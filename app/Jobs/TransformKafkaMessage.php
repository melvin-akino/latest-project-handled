<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\{Events, EventMarket, MasterEvent, MasterEventLink, MasterEventMarket, MasterEventMarketLink, MasterEventMarketLog, MasterLeague, Provider, Teams};
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
        $swoole  = app('swoole');
        $wsTable = $swoole->wsTable;

        if (empty((array) $this->message->data)) {
            return;
        }

        $timestampSwtId = implode(':', [
            /** LEAGUE NAME */
            'ln:' . $this->message->data->leagueName,
            /** REFERENCE SCHEDULE */
            'rs:' . $this->message->data->referenceSchedule
        ]);

        if ($wsTable->exists($timestampSwtId)) {
            $swooleTS = $wsTable[$timestampSwtId]['value'];

            if ($swooleTS > $this->message->request_ts) {
                return;
            }
        }

        $wsTable[$timestampSwtId]['value'] = $this->message->request_ts;

        $toInsert    = [];
        $toTransform = true;
        $updated     = false;

        /** DATABASE TABLES */
        /** LOOK-UP TABLES */
        $providersTable     = $swoole->providersTable;
        $sportsTable        = $swoole->sportsTable;
        $leaguesTable       = $swoole->leaguesTable;
        $teamsTable         = $swoole->teamsTable;
        $eventsTable        = $swoole->eventsTable;
        $oddTypesTable      = $swoole->oddTypesTable;
        $sportOddTypesTable = $swoole->sportOddTypesTable;
        $eventMarketsTable  = $swoole->eventMarketsTable;
        $transformedTable   = $swoole->transformedTable;

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
        $sportSwtId = "sId:" . $this->message->data->sport;

        if ($sportsTable->exists($sportSwtId)) {
            $sportId = $sportsTable->get($sportSwtId)['id'];
            $sportName = $sportsTable->get($sportSwtId)['sport'];
        } else {
            throw new Exception("Sport doesn't exist");
        }

        /**
         * LEAGUES (MASTER) Swoole Table
         *
         * @ref config.laravels.leagues
         *
         * @var $leaguesTable    swoole_table
         *      $leagueSwtId     swoole_table_key    "sId:<$sportId>:pId:<$providerId>:league:<slug($rawLeague)>"
         *      $multiLeagueId   swoole_table_value  int
         */
        $leagueSwtId = implode(':', [
            "sId:" . $sportId,
            "pId:" . $providerId,
            "league:" . Str::slug($this->message->data->leagueName)
        ]);

        if ($leaguesTable->exists($leagueSwtId)) {
            $multiLeagueId    = $leaguesTable->get($leagueSwtId)['id'];
            $masterLeagueName = $leaguesTable->get($leagueSwtId)['master_league_name'];
        } else {
            $toTransform = false;
        }

        $multiTeam = [];
        $rawTeams = [];
        $competitors = [
            'home' => $this->message->data->homeTeam,
            'away' => $this->message->data->awayTeam,
        ];

        foreach ($competitors AS $key => $row) {
            /**
             * TEAMS (MASTER) Swoole Table
             *
             * @ref config.laravels.teams
             *
             * @var $teamsTable  swoole_table
             *      $teamSwtId   swoole_table_key    "pId:<$providerId>:teamName:<slug($rawTeamName)>"
             */
            $teamSwtId = implode(':', [
                "pId:" . $providerId,
                "teamName:" . Str::slug($competitors[$key])
            ]);

            if ($teamsTable->exists($teamSwtId)) {
                $multiTeam[$key]['id']   = $teamsTable->get($teamSwtId)['id'];
                $multiTeam[$key]['name'] = $teamsTable->get($teamSwtId)['team_name'];
            } else {
                $toTransform = false;
            }
        }

        if (!empty($masterLeagueName) && !empty($multiTeam)) {
            /**
             * EVENTS (MASTER) Swoole Table
             *
             * @ref config.laravels.events
             *
             * @var $arrayEvents  array               Contains Event information extracted from game data json
             *      $eventsTable  swoole_table
             *      $eventSwtId   swoole_table_key    "sId:<$sportId>:masterLeagueId:<$masterLeagueId>:eId:<$rawEventId>"
             *      $event        swoole_table_value  string
             */
            $eventSwtId = implode(':', [
                "sId:" . $sportId,
                "pId:" . $providerId,
                "eventIdentifier:" . $this->message->data->events[0]->eventId
            ]);

            if ($eventsTable->exists($eventSwtId)) {
                $eventId            = $eventsTable->get($eventSwtId)['id'];
                $uid                = $eventsTable->get($eventSwtId)['master_event_unique_id'];
                $masterTeamHomeName = $eventsTable->get($eventSwtId)['master_home_team_name'];
                $masterTeamAwayName = $eventsTable->get($eventSwtId)['master_away_team_name'];
            } else {
                $toTransform    = false;
                $masterTeamHome = $multiTeam['home']['name'];
                $masterTeamAway = $multiTeam['away']['name'];

                $uid = implode('-', [
                    date("Ymd", strtotime($this->message->data->referenceSchedule)),
                    $sportId,
                    $multiLeagueId,
                    $this->message->data->events[0]->eventId
                ]);

                $array = [
                    'event_identifier'       => $this->message->data->events[0]->eventId,
                    'sport_id'               => $sportId,
                    'master_event_unique_id' => $uid,
                    'master_league_name'     => $masterLeagueName,
                    'master_home_team_name'  => $masterTeamHome,
                    'master_away_team_name'  => $masterTeamAway,
                    'game_schedule'          => $this->message->data->schedule,
                    'ref_schedule'           => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule))
                ];

                $eventsTable->set($eventSwtId, $array);

                $eventModel = MasterEvent::create($array);
                $rawEventId = $eventModel->id;

                $eventsTable[$eventSwtId]['id'] = $rawEventId;
            }
        }

        if (!empty($uid)) {
            $arrayEvents = $this->message->data->events;

            /** loop each `events` */
            foreach ($arrayEvents AS $keyEvent => $event) {
                $arrayOddTypes = [];
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
                        "sId:" . $sportId,
                        "oddType:" . Str::slug($columns->oddsType)
                    ]);

                    if (!$sportOddTypesTable->exist($sportOddTypeSwtId)) {
                        throw new Exception("Sport Odds Type doesn't exist");
                    }

                    $arrayOddTypes[] = $columns->oddsType;

                    /** loop each `marketSelection` from each `market_odds` */
                    foreach ($columns->marketSelection AS $markets) {
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
                            if (strpos($key, 'pId:' . $providerId . ':meUID:' . $uid . ':memUID:') == 0) {
                                $found = true;

                                if ($row['bet_identifier'] == $markets->market_id) {
                                    if ($row['odds'] != $markets->odds) {
                                        $eventMarketsTable[$key]['odds'] = $markets->odds;
                                        $updated = true;

                                        /** Set Updated Odds to WS Swoole Table */
                                        $WSOddsSwtId = "marketId:" . $markets->market_id;

                                        if ($wsTable->exists($WSOddsSwtId)) {
                                            $wsTable->set($WSOddsSwtId, [ 'value' => $markets->odds ]);
                                        }
                                    }
                                }

                                break;
                            }
                        }

                        if (!$found) {
                            $memUID           = uniqid();
                            $toTransform      = false;
                            $eventMarketSwtId = implode(':', [
                                "pId:" . $providerId,
                                "meUID:" . $uid,
                                "memUID:" . $memUID
                            ]);

                            $array = [
                                'odd_type_id'                   => $oddTypeId,
                                'master_event_market_unique_id' => $memUID,
                                'master_event_unique_id'        => $uid,
                                'provider_id'                   => $providerId,
                                'odds'                          => $markets->odds,
                                'odd_label'                     => array_key_exists('points', $markets) ? $markets->points : "",
                                'bet_identifier'                => $markets->market_id,
                                'is_main'                       => $event->market_type == 1 ? true : false,
                                'market_flag'                   => strtoupper($markets->indicator),
                            ];

                            $eventMarketsTable->set($eventMarketSwtId, $array);

                            $eventModel = MasterEventMarket::create([
                                'master_event_unique_id'        => $uid,
                                'master_event_market_unique_id' => $memUID,
                                'odd_type_id'                   => $oddTypeId,
                                'is_main'                       => $array['is_main'],
                                'market_flag'                   => $array['market_flag'],
                            ]);

                            $id = $eventModel->id;

                            $eventMarketsTable[$eventMarketSwtId]['id'] = $id;

                            /** TO INSERT */

                            $toInsert['Events'][] = [
                                'sport_id'         => $sportId,
                                'provider_id'      => $providerId,
                                'event_identifier' => $this->message->data->events[0]->eventId,
                                'league_name'      => $this->message->data->leagueName,
                                'home_team_name'   => $this->message->data->homeTeam,
                                'away_team_name'   => $this->message->data->awayTeam,
                                'ref_schedule'     => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                                'game_schedule'    => $this->message->data->schedule,
                            ];

                            $toInsert['MasterEvent'][] = [
                                'sport_id'               => $sportId,
                                'master_event_unique_id' => $uid,
                                'master_league_name'     => $masterLeagueName,
                                'master_home_team_name'  => $multiTeam['home']['name'],
                                'master_away_team_name'  => $multiTeam['away']['name'],
                                'ref_schedule'           => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                                'game_schedule'          => $this->message->data->schedule,
                                'score'                  => $this->message->data->home_score . "|" . $this->message->data->away_score,
                                'running_time'           => $this->message->data->running_time,
                                'home_penalty'           => $this->message->data->home_redcard,
                                'away_penalty'           => $this->message->data->away_redcard,
                            ];

                            $toInsert['MasterEventLink'][] = [
                                'event_id'        => "",
                                'master_event_id' => "",
                            ];

                            $toInsert['MasterEventMarket'][] = [
                                'sport_id'                      => $sportId,
                                'master_event_unique_id'        => $uid,
                                'odd_type_id'                   => $oddTypeId,
                                'master_event_market_unique_id' => $memUID,
                                'is_main'                       => $array['is_main'],
                                'market_flag'                   => $array['market_flag'],
                            ];

                            $toInsert['EventMarket'][] = [
                                'provider_id'            => $providerId,
                                'master_event_unique_id' => $uid,
                                'odd_type_id'            => $oddTypeId,
                                'odds'                   => $markets->odds,
                                'odd_label'              => $array['odd_label'],
                                'bet_identifier'         => $markets->market_id,
                                'is_main'                => $array['is_main'],
                                'market_flag'            => $array['market_flag'],
                            ];

                            $toInsert['MasterEventMarketLink'][] = [
                                'event_market_id'        => "",
                                'master_event_market_id' => "",
                            ];

                            $toInsert['MasterEventMarketLog'][] = [
                                'master_event_unique_id' => $uid,
                                'odd_type_id'            => $oddTypeId,
                                'odds'                   => $markets->odds,
                                'odd_label'              => $array['odd_label'],
                                'is_main'                => $array['is_main'],
                                'market_flag'            => $array['market_flag'],
                            ];
                        }
                    }
                }
            }
        }
        /** `events` key from json data */

        /** Data Transformation */
        if ($toTransform && !$updated) {
            $transformedJSON = [
                'timestamp'     => $this->message->request_ts,
                'uid'           => $uid,
                'sport_id'      => $sportId,
                'sport'         => $sportName,
                'provider_id'   => $providerId,
                'event_id'      => $this->message->data->events[0]->eventId,
                'game_schedule' => $this->message->data->schedule,
                'league_name'   => $masterLeagueName,
                'home'          => [
                    'name'    => $multiTeam['home']['name'],
                    'score'   => $this->message->data->home_score,
                    'penalty' => $this->message->data->home_redcard
                ],
                'away'          => [
                    'name'    => $multiTeam['away']['name'],
                    'score'   => $this->message->data->away_score,
                    'penalty' => $this->message->data->away_redcard
                ],
                'ref_schedule'  => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                'running_time'  => $this->message->data->running_time,
                'market_odds'   => [],
            ];

            /** Forming Main Markets */
            foreach ($this->message->data->events[0]->market_odds AS $columns) {
                if (in_array($columns->oddsType, $arrayOddTypes)) {
                    foreach ($columns->marketSelection AS $_market) {
                        $_marketOdds = $_market->odds;
                        $_marketPoints = "";

                        if (gettype($_market->odds) == 'string') {
                            $_marketOdds = explode(' ', $_market->odds);

                            if (count($_marketOdds) > 1) {
                                $_marketPoints = $_marketOdds[0];
                                $_marketOdds   = $_marketOdds[1];
                            }
                        }

                        $transformedJSON['market_odds']['main'][$columns->oddsType][strtolower($_market->indicator)] = [
                            'odds'      => (float)$_marketOdds,
                            'market_id' => $_market->market_id
                        ];

                        if (array_key_exists('points', $_market)) {
                            $_marketPoints = $_market->points;
                            $transformedJSON['market_odds']['main'][$columns->oddsType][strtolower($_market->indicator)]['points'] = $_marketPoints;
                        }
                    }
                }
            }

            /** Forming Other Markets */
            $i = 0;

            foreach ($this->message->data->events[1]->market_odds AS $columns) {
                if (in_array($columns->oddsType, $arrayOddTypes)) {
                    foreach ($columns->marketSelection AS $_market) {
                        $_marketOdds = $_market->odds;
                        $_marketPoints = "";

                        if (gettype($_market->odds) == 'string') {
                            $_marketOdds = explode(' ', $_market->odds);

                            if (count($_marketOdds) > 1) {
                                $_marketPoints = $_marketOdds[0];
                                $_marketOdds   = $_marketOdds[1];
                            }
                        }

                        $transformedJSON['market_odds']['other'][$columns->oddsType][strtolower($_market->indicator)] = [
                            'odds'      => (float)$_marketOdds,
                            'market_id' => $_market->market_id
                        ];

                        if (array_key_exists('points', $_market)) {
                            $_marketPoints = $_market->points;
                            $transformedJSON['market_odds']['other'][$i][$columns->oddsType][strtolower($_market->indicator)]['points'] = $_marketPoints;
                        }
                    }

                    $i++;
                }
            }

            $transformedSwtId = "uid:" . $uid;

            if (!$transformedTable->exists($transformedSwtId)) {
                $transformedTable->set($transformedSwtId, $transformedJSON);
            }

            var_dump([ 'toinsert' => $toInsert ]);

            /** TODO: Insert to DB WHERE $key == Model Name */
            // $insertIds = [];

            // foreach ($toInsert AS $key => $row) {
            //     $insertIds[$key] = $key::create($row);
            // }
        }
    }
}
