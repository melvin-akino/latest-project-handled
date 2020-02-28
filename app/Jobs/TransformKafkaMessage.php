<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\{Events, MasterEvent, MasterEventMarket, MasterLeague, Provider, Teams};
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
//        var_dump($this->message);
    }

    public function handle()
    {
        if (empty((array) $this->message->data)) {
            return;
        }
        var_dump($this->message->data->provider);
        var_dump($this->message->data->schedule);
        var_dump($this->message->data->sport);
        var_dump($this->message->data->leagueName);
        var_dump($this->message->data->homeTeam);
        var_dump($this->message->data->awayTeam);
        var_dump("------------------------------------");

        $toInsert = [];
        $toTransform = true;
        $updated = false;

        $swoole = app('swoole');

        /** DATABASE TABLES */
        /** LOOK-UP TABLES */
        $providersTable = $swoole->providersTable;
        $sportsTable = $swoole->sportsTable;
        // $rawLeaguesTable = $swoole->rawLeaguesTable;
        $leaguesTable = $swoole->leaguesTable;
        // $rawTeamsTable = $swoole->rawTeamsTable;
        $teamsTable = $swoole->teamsTable;
        // $rawEventsTable = $swoole->rawEventsTable;
        $eventsTable = $swoole->eventsTable;
        $oddTypesTable = $swoole->oddTypesTable;
        $sportOddTypesTable = $swoole->sportOddTypesTable;
        // $rawEventMarketsTable = $swoole->rawEventMarketsTable;
        $eventMarketsTable = $swoole->eventMarketsTable;
        $transformedTable = $swoole->transformedTable;

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
            if (env('APP_DEBUG')) {
                Log::debug('SWT ProvidersTable Key - ' . $providerSwtId);
                Log::debug('SWT ProvidersTable Provider ID - ' . $providerId);
            }
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
            if (env('APP_DEBUG')) {
                Log::debug('SWT SportsTable Key - ' . $sportSwtId);
                Log::debug('SWT SportsTable Sport ID - ' . $sportId);
            }
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
        // $rawLeagueSwtId = implode(':', [
        //     "sId:" . $sportId,
        //     "pId:" . $providerId,
        //     "league:" . Str::slug($this->message->data->leagueName)
        // ]);

        // if ($rawLeaguesTable->exist($rawLeagueSwtId)) {
        //     $rawLeagueId = $rawLeaguesTable->get($rawLeagueSwtId)['id'];
        // } else {
        //     $leaguesTable->set($rawLeagueSwtId,
        //         [
        //             'provider_id' => $providerId,
        //             'sport_id'    => $sportId,
        //             'league'      => $this->message->data->leagueName
        //         ]);

        //     $leagueModel = League::create([
        //         'sport_id'    => $sportId,
        //         'provider_id' => $providerId,
        //         'league'      => $this->message->data->leagueName
        //     ]);
        //     $rawLeagueId = $leagueModel->id;

        //     $leaguesTable[$rawLeagueSwtId]['id'] = $rawLeagueId;

        //     $toTransform = false;
        // }

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
            if (env('APP_DEBUG')) {
                Log::debug('SWT LeaguesTable Key - ' . $leagueSwtId);
                Log::debug('SWT LeaguesTable League ID - ' . $multiLeagueId);
                Log::debug('SWT LeaguesTable League Name - ' . $masterLeagueName);
            }
        } else {
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
        $rawTeams = [];
        $competitors = [
            'home' => $this->message->data->homeTeam,
            'away' => $this->message->data->awayTeam,
        ];

        foreach ($competitors AS $key => $row) {
            // $rawTeamSwtId = implode(':', [
            //     "pId:" . $providerId,
            //     "team:" . Str::slug($competitors[$key])
            // ]);

            // if ($rawTeamsTable->exists($rawTeamSwtId)) {
            //     $rawTeamName = $rawTeamsTable->get($rawTeamSwtId)['team'];
            //     $rawTeams[$key]['id'] = $rawTeamsTable->get($rawTeamSwtId)['id'];
            // } else {
            //     $rawTeamsTable->set('pId:' . $providerId . ':team:' . Str::slug($competitors[$key]),
            //         ['team' => $competitors[$key], 'provider_id' => $providerId]);

            //     $teamsModel = Teams::create([
            //         'team' => $competitors[$key],
            //         'provider_id' => $providerId
            //     ]);

            //     $rawTeamsTable['pId:' . $providerId . ':team:' . Str::slug($competitors[$key])]['id'] = $teamsModel->id;

            //     $rawTeams[$key]['id'] = $teamsModel->id;
            //     $toTransform = false;
            // }

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
var_dump($teamSwtId);
            if ($teamsTable->exists($teamSwtId)) {
                $multiTeam[$key]['id']   = $teamsTable->get($teamSwtId)['id'];
                $multiTeam[$key]['name'] = $teamsTable->get($teamSwtId)['team_name'];
                if (env('APP_DEBUG')) {
                    Log::debug('SWT TeamsTable Key - ' . $teamSwtId);
                    Log::debug('SWT TeamsTable Team ID - ' . $multiTeam[$key]['id']);
                    Log::debug('SWT TeamsTable Team Name - ' . $multiTeam[$key]['name']);
                }
            } else {
                $toTransform = false;
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
        // $rawEventSwtId = implode(':', [
        //     "sId:" . $sportId,
        //     "pId:" . $providerId,
        //     "eId:" . $this->message->data->events[0]->eventId
        // ]);

        // if ($rawEventsTable->exists($rawEventSwtId)) {
        //     $rawEventId = $rawEventsTable->get($rawEventSwtId)['id'];
        // } else {
        //     $array = [
        //         'event_identifier'   => $this->message->data->events[0]->eventId,
        //         'sport_id'           => $sportId,
        //         'provider_id'        => $providerId,
        //         'league_name'        => $this->message->data->leagueName,
        //         'home_team_name'     => $this->message->data->homeTeam,
        //         'away_team_name'     => $this->message->data->awayTeam,
        //         'game_schedule'      => $this->message->data->schedule,
        //         'ref_schedule'       => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule))
        //     ];

        //     $eventModel  = Events::create($array);
        //     $rawEventId  = $eventModel->id;
        //     $array['id'] = $eventModel->id;

        //     $rawEventsTable->set($rawEventSwtId, $array);

        //     $toTransform = false;
        // }

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
                if (env('APP_DEBUG')) {
                    Log::debug('SWT eventsTable Key - ' . $eventSwtId);
                    Log::debug('SWT eventsTable Values - ' . json_encode($array));
                }

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

                    if (empty($arrayOddTypes)) {
                        $arrayOddTypes[] = $columns->oddsType;
                    }

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
                        // $rawEventMarketSwtId = implode(':', [
                        //     "lId:" . $rawLeagueId,
                        //     "pId:" . $providerId,
                        //     "eId:" . $rawEventId
                        // ]);

                        // if ($rawEventMarketsTable->exists($rawEventMarketSwtId)) {
                        //     $rawEventMarketId = $rawEventMarketsTable->get($rawEventMarketSwtId)['id'];
                        //     $rawEventMarketOdds = $rawEventMarketsTable->get($rawEventMarketSwtId)['odds'];
                        // } else {
                        //     $toInsert['raw_event_markets'] = [];

                        //     $rawEventMarketsTable->set($eventSwtId, [
                        //         'league_id'      => $rawLeagueId,
                        //         'event_id'       => $rawEventId,
                        //         'odd_type_id'    => $oddTypeId,
                        //         'provider_id'    => $providerId,
                        //         'odds'           => $markets->odds,
                        //         'bet_identifier' => $markets->market_id,
                        //         'is_main'        => $keyEvent == 0 ? 1 : 0,
                        //         'market_flag'    => strtoupper($markets->indicator)
                        //     ]);

                        //     if (!empty($markets->points)) {
                        //         $rawEventMarketsTable[$eventSwtId] = ['odd_label' => $markets->points];
                        //     }
                        // }

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
//                                if ($row['event_market_id'] == $rawEventMarketId) {
                                    $found = true;

                                    if ($row['odds'] != $markets->odds) {
                                        $eventMarketsTable[$key]['odds'] = $markets->odds;
                                        $updated = true;
                                    }

                                    break;
//                                }
                            }
                        }

                        if (!$found) {
                            $memUID           = uniqid();
                            $toTransform      = false;
                            $eventMarketSwtId = implode(':', [
                                "pId:" . $providerId,
                                "meUniqueId:" . $uid,
                                "memUniqueId:" . $memUID
                            ]);

                            $array = [
                                'odd_type_id'                   => $oddTypeId,
                                'master_event_market_unique_id' => $memUID,
                                'master_event_unique_id'        => $uid,
                                'event_market_id'               => $rawEventMarketId,
                                'provider_id'                   => $providerId,
                                'odds'                          => $markets->odds,
                                'odd_label'                     => array_key_exists('points', $markets) ? $markets->points : "",
                                'bet_identifier'                => $markets->market_id,
                                'is_main'                       => $event->market_type == 1 ? true : false,
                                'market_flag'                   => strtoupper($markets->indicator),
                            ];

                            $eventMarketsTable->set($eventMarketSwtId, $array);

                            $eventModel = MasterEventMarket::create($array);
                            $id         = $eventModel->id;

                            $eventMarketsTable[$eventMarketSwtId]['id'] = $id;
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
                        $_marketOdds = $_market->odds;
                        $_marketPoints = $_market->points;

                        if (gettype($_market->odds) == 'string') {
                            $_marketOdds   = explode(' ', $_market->odds);
                            $_marketPoints = $_marketOdds[0];
                            $_marketOdds   = $_marketOdds[1];
                        }

                        $transformedJSON['market_odds']['main'][$columns->oddsType][strtolower($_market->indicator)] = [
                            'odds'      => (float)$_marketOdds,
                            'market_id' => $_market->market_id
                        ];

                        if (array_key_exists('points', $_market)) {
                            $transformedJSON['market_odds']['main'][$columns->oddsType][strtolower($_market->indicator)]['points'] = $_marketPoints;
                        }
                    }
                }
            }

            /** Forming Other Markets */
            $i = 0;

            foreach ($this->message->data->events[1]->market_odds AS $columns) {
                if (in_array($columns->oddsType, $arrayOddTypes)) {
                    foreach ($columns->marketSelections AS $_market) {
                        $_marketOdds = $_market->odds;
                        $_marketPoints = $_market->points;

                        if (gettype($_market->odds) == 'string') {
                            $_marketOdds   = explode(' ', $_market->odds);
                            $_marketPoints = $_marketOdds[0];
                            $_marketOdds   = $_marketOdds[1];
                        }

                        $transformedJSON['market_odds']['other'][$columns->oddsType][strtolower($_market->indicator)] = [
                            'odds'      => (float)$_marketOdds,
                            'market_id' => $_market->market_id
                        ];

                        if (array_key_exists('points', $_market)) {
                            $transformedJSON['market_odds']['other'][$i][$columns->oddsType][strtolower($_market->indicator)]['points'] = $_marketPoints;
                        }
                    }

                    $i++;
                }
            }

            $transformedSwtId = "uid:" . $uid;

            if (!$transformedTable->exists($transformedSwtId)) {
                $transformedTable->set($transformedSwtId, json_encode($transformedJSON));
                if (env('APP_DEBUG')) {
                    Log::debug('TransformedTable Key - ' . $transformedSwtId);
                    Log::debug('TransformedTable VALUE - ' . $transformedJSON);
                }
            }
        }
    }
}
