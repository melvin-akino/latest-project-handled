<?php

namespace App\Jobs;

use App\Tasks\{
    TransformationEventCreation,
    TransformationEventMarketCreation
};

use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Exception;

class TransformKafkaMessageOdds implements ShouldQueue
{
    use Dispatchable;

    protected $message;
    protected $swoole;
    protected $disregard = [
        'No. of Corners',
        'No. of Bookings',
        'Extra Time',
        'To Qualify',
        'Winner',
        'PK(Handicap)',
        'PK(Over/Under)',
        'games (e.g',
        'Days (',
        ' Game',
        'Corners',
        'borders',
        'To Win Final',
        'To Finish 3rd',
        'To Advance',
        '(w)',
        '(n)',
        'Home Team',
        'Away Team',
        'To Win',
        'TEST'
    ];
    protected $start;

    public function __construct($message)
    {
        $this->message = json_decode($message->payload);
    }

    public function handle()
    {
        $swoole  = app('swoole');
        $wsTable = $swoole->wsTable;

        if (empty($this->message->data)) {
            return;
        }

        foreach ($this->disregard AS $disregard) {
            if (strpos($this->message->data->leagueName, $disregard) > -1) {
                return;
            }
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

        $leagueLookupId = null;
        foreach ($wsTable as $key => $value) {
            if (strpos($key, 'leagueLookUpId:') === 0) {
                if ($value['value'] == $this->message->data->leagueName) {
                    $leagueLookupId = substr($key, strlen('leagueLookUpId:'));
                    break;
                }
            }
        }

        /**
         * LEAGUES (MASTER) Swoole Table
         *
         * @ref config.laravels.leagues
         *
         * @var $leaguesTable    swoole_table
         *      $leagueSwtId     swoole_table_key    "sId:<$sportId>:pId:<$providerId>:leagueLookUpId:$leagueLookUpId"
         *      $multiLeagueId   swoole_table_value  int
         */
        $leagueSwtId = implode(':', [
            "sId:" . $sportId,
            "pId:" . $providerId,
            "leagueLookUpId:" . $leagueLookupId
        ]);

        if ($leaguesTable->exists($leagueSwtId)) {
            $multiLeagueId    = $leaguesTable->get($leagueSwtId)['id'];
            $masterLeagueName = $leaguesTable->get($leagueSwtId)['master_league_name'];
        } else {
            throw new Exception("League doesn't exist");
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
                "teamName:" . Str::slug($row)
            ]);

            if ($teamsTable->exists($teamSwtId)) {
                $multiTeam[$key]['id']   = $teamsTable->get($teamSwtId)['id'];
                $multiTeam[$key]['name'] = $teamsTable->get($teamSwtId)['team_name'];
            } else {
                $toTransform = false;
            }
        }

        if (!empty($masterLeagueName) && !empty($multiTeam) && count($multiTeam) == 2) {
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
                $masterTeamHome = $multiTeam['home']['name'];
                $masterTeamAway = $multiTeam['away']['name'];

                $uid = implode('-', [
                    date("Ymd", strtotime($this->message->data->referenceSchedule)),
                    $sportId,
                    $multiLeagueId,
                    $this->message->data->events[0]->eventId
                ]);

                $masterEventData = [
                    'event_identifier'       => $this->message->data->events[0]->eventId,
                    'sport_id'               => $sportId,
                    'master_event_unique_id' => $uid,
                    'master_league_name'     => $masterLeagueName,
                    'master_home_team_name'  => $masterTeamHome,
                    'master_away_team_name'  => $masterTeamAway,
                    'game_schedule'          => $this->message->data->schedule,
                    'ref_schedule'           => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                    'score'                  => $this->message->data->home_score . ' - ' . $this->message->data->away_score,
                    'running_time'           => $this->message->data->running_time,
                    'home_penalty'           => $this->message->data->home_redcard,
                    'away_penalty'           => $this->message->data->away_redcard,
                ];

                $eventsTable->set($eventSwtId, $masterEventData);
            }
        }

        $updatedOdds = [];

        if (!empty($uid)) {
            $arrayEvents     = $this->message->data->events;
            $counter         = 0;
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
                    'name'    => $masterTeamHome,
                    'score'   => $this->message->data->home_score,
                    'penalty' => $this->message->data->home_redcard
                ],
                'away'          => [
                    'name'    => $masterTeamAway,
                    'score'   => $this->message->data->away_score,
                    'penalty' => $this->message->data->away_redcard
                ],
                'ref_schedule'  => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                'running_time'  => $this->message->data->running_time,
                'market_odds'   => [],
            ];

            foreach ($arrayEvents AS $keyEvent => $event) {
                if (!empty($event)) {
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
                            "sId:"     . $sportId,
                            "oddType:" . Str::slug($columns->oddsType)
                        ]);

                        if (!$sportOddTypesTable->exist($sportOddTypeSwtId)) {
                            throw new Exception("Sport Odds Type doesn't exist");
                        }

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

                            $marketOdds   = $markets->odds;
                            $marketPoints = "";

                            if (gettype($marketOdds) == 'string') {
                                $marketOdds = explode(' ', $markets->odds);

                                if (count($marketOdds) > 1) {
                                    $marketPoints = $marketOdds[0];
                                    $marketOdds   = $marketOdds[1];
                                } else {
                                    $marketOdds   = $marketOdds[0];
                                }
                            }

                            $marketOdds = trim($marketOdds) == '' ? 0 : (float) $marketOdds;
                            $transformedJSON['market_odds']['main'][$columns->oddsType][strtolower($markets->indicator)] = [
                                'odds'      => $marketOdds,
                                'market_id' => $markets->market_id
                            ];

                            if (array_key_exists('points', $markets)) {
                                $marketPoints = $markets->points;

                                if ($counter == 0) {
                                    $transformedJSON['market_odds']['main'][$columns->oddsType][strtolower($markets->indicator)]['points'] = $marketPoints;
                                } else {
                                    $transformedJSON['market_odds']['other'][($counter - 1)][$columns->oddsType][strtolower($markets->indicator)]['points'] = $marketPoints;
                                }
                            }

                            $memUID = uniqid();

                            if ($eventMarketsTable->exist('pId:' . $providerId . ':meUID:' . $uid . ':betIdentifier:' . $markets->market_id)) {
                                $memUID = $eventMarketsTable->get('pId:' . $providerId . ':meUID:' . $uid . ':betIdentifier:' . $markets->market_id)['bet_identifier'];
                                $odds   = $eventMarketsTable->get('pId:' . $providerId . ':meUID:' . $uid . ':betIdentifier:' . $markets->market_id)['odds'];

                                if ($odds != $marketOdds) {
                                    $eventMarketsTable[$key]['odds'] = $marketOdds;
                                    $updated = true;

                                    $updatedOdds[] = ['market_id' => $markets->market_id, 'odds' => $marketOdds];
                                }

                                break;
                            }

                            $eventMarketSwtId = implode(':', [
                                "pId:" . $providerId,
                                "meUID:" . $uid
                            ]);

                            $array = [
                                'odd_type_id'                   => $oddTypeId,
                                'master_event_market_unique_id' => $memUID,
                                'master_event_unique_id'        => $uid,
                                'provider_id'                   => $providerId,
                                'odds'                          => $marketOdds,
                                'odd_label'                     => $marketPoints,
                                'bet_identifier'                => $markets->market_id,
                                'is_main'                       => $event->market_type == 1 ? true : false,
                                'market_flag'                   => strtoupper($markets->indicator),
                            ];

                            $eventMarketsTable->set($eventMarketSwtId, $array);

                            /** TO INSERT */
                            $toInsert['MasterEvent']['swtKey'] = implode(':', [
                                "sId:" . $sportId,
                                "pId:" . $providerId,
                                "eventIdentifier:" . $this->message->data->events[0]->eventId
                            ]);

                            $toInsert['MasterEvent']['data'] = [
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

                            $toInsert['Event']['data'] = [
                                'sport_id'         => $sportId,
                                'provider_id'      => $providerId,
                                'event_identifier' => $this->message->data->events[0]->eventId,
                                'league_name'      => $this->message->data->leagueName,
                                'home_team_name'   => $this->message->data->homeTeam,
                                'away_team_name'   => $this->message->data->awayTeam,
                                'ref_schedule'     => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                                'game_schedule'    => $this->message->data->schedule,
                            ];

                            if (empty($eventId)) {
                                $task = new TransformationEventCreation($toInsert);
                                Task::deliver($task);
                            }

                            $toInsert['MasterEventMarket']['isNew']  = empty($eventId) ? true : false;
                            $toInsert['MasterEventMarket']['swtKey'] = "marketId:" . $markets->market_id;
                            $toInsert['MasterEventMarket']['data']   = [
                                'master_event_unique_id'        => $uid,
                                'odd_type_id'                   => $oddTypeId,
                                'master_event_market_unique_id' => $memUID,
                                'is_main'                       => $array['is_main'],
                                'market_flag'                   => $array['market_flag'],
                            ];

                            $toInsert['EventMarket']['data'] = [
                                'provider_id'            => $providerId,
                                'master_event_unique_id' => $uid,
                                'odd_type_id'            => $oddTypeId,
                                'odds'                   => $marketOdds,
                                'odd_label'              => $array['odd_label'],
                                'bet_identifier'         => $markets->market_id,
                                'is_main'                => $array['is_main'],
                                'market_flag'            => $array['market_flag'],
                            ];

                            $toInsert['MasterEventMarketLog']['data'] = [
                                'odd_type_id' => $oddTypeId,
                                'odds'        => $marketOdds,
                                'odd_label'   => $array['odd_label'],
                                'is_main'     => $array['is_main'],
                                'market_flag' => $array['market_flag'],
                            ];

                            $task = new TransformationEventMarketCreation($toInsert);
                            Task::deliver($task);
                        }
                    }
                }

                $counter++;
            }

            $transformedSwtId = "uid:" . $uid . ":pId:" . $providerId;
            if (!$transformedTable->exists($transformedSwtId)) {
                $transformedTable->set($transformedSwtId, ['value' => json_encode($transformedJSON)]);
            }
        }

        if ($updated) {
            /** Set Updated Odds to WS Swoole Table */
            $WSOddsSwtId = "updatedEvents:" . $uid;
            $wsTable->set($WSOddsSwtId, [ 'value' => json_encode($updatedOdds) ]);

            array_map(function($odds) use ($wsTable, $uid) {
                TransformationEventMarketUpdate::dispatch($odds['market_id'], $odds['odds']);
            }, $updatedOdds);
        }
    }
}
