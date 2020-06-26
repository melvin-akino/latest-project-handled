<?php

namespace App\Handlers;

use App\Models\League;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Exception;
use stdClass;
use Illuminate\Support\Facades\Redis;

class OddsTransformationHandler
{
    protected $message;
    protected $internalParameters;
    protected $updated   = false;
    protected $uid       = null;
    protected $dbOptions = [
        'event-only'       => false,
        'is-event-new'     => true,
        'in-masterlist'    => true
    ];

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

    public function init($message, $internalParameters)
    {
        $this->message            = $message;
        $this->internalParameters = $internalParameters;
        return $this;
    }

    public function handle()
    {
        try {
            $swoole                             = app('swoole');
            $toInsert                           = [];
            $subTasks = [];

            /** DATABASE TABLES */
            /** LOOK-UP TABLES */
            $eventsTable              = $swoole->eventsTable;
            $oddTypesTable            = $swoole->oddTypesTable;
            $sportOddTypesTable       = $swoole->sportOddTypesTable;
            $eventMarketsTable        = $swoole->eventMarketsTable;
            $eventScheduleChangeTable = $swoole->eventScheduleChangeTable;

            list(
                'providerId' => $providerId,
                'sportId'    => $sportId,
                'parameters' => $parameters
                ) = $this->internalParameters;

            list(
                'master_league_id'      => $masterLeagueId,
                'master_team_home_id'   => $multiTeam['home']['id'],
                'master_team_away_id'   => $multiTeam['away']['id']
                ) = $parameters;

            $leagueId = $this->saveLeaguesData($swoole, $providerId,  $sportId, $this->message->data->leagueName);
            $team = $this->saveTeamsData($swoole, $providerId,  $sportId, $this->message->data->homeTeam, $this->message->data->awayTeam);

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

            $doesExist = false;
            if (!empty($masterLeagueId) && !empty($multiTeam['home']['id']) && !empty($multiTeam['away']['id'])) {
                foreach ($eventsTable as $key => $value) {
                    if (
                        $sportId == $value['sport_id'] &&
                        $multiTeam['home']['id'] == $value['master_team_home_id'] &&
                        $multiTeam['away']['id'] == $value['master_team_away_id'] &&
                        $this->message->data->schedule == $value['game_schedule'] &&
                        date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)) == $value['ref_schedule'] &&
                        $masterLeagueId == $value['master_league_id']
                    ) {
                        $eventSwtId = $key;
                        $eventsData = $value;
                        $doesExist  = true;
                        break;
                    }
                }
            } else {
                $this->dbOptions['in-masterlist'] = false;
            }
            if ($doesExist) {
                $eventId = $eventsData['id'];
                $uid     = $eventsData['master_event_unique_id'];

                $masterTeamHomeId = $eventsData['master_team_home_id'];
                $masterTeamAwayId = $eventsData['master_team_away_id'];

                $teamHomeId = $eventsData['team_home_id'];
                $teamAwayId = $eventsData['team_away_id'];

                if ($this->message->data->schedule == 'early' && $eventsData['game_schedule'] == 'today') {
                    Log::info("Transformation ignored - event is already in today");
                    return;
                }

                if ($this->message->data->schedule == 'today' && $eventsData['game_schedule'] == 'inplay') {
                    Log::info("Transformation ignored - event is already in play");
                    return;
                }

                if (($eventsData['game_schedule'] != "") && ($eventsData['game_schedule'] != $this->message->data->schedule)) {
                    $eventScheduleChangeTable->set('eventScheduleChange:' . $uid, [
                        'value' => json_encode([
                            'uid'           => $uid,
                            'game_schedule' => $this->message->data->schedule,
                            'sport_id'      => $sportId
                        ])
                    ]);
                }
            } else {
                $masterTeamHomeId = $multiTeam['home']['id'];
                $masterTeamAwayId = $multiTeam['away']['id'];

                $teamHomeId = $team['home']->id;
                $teamAwayId = $team['away']->id;

                $uid = implode('-', [
                    date("Ymd", strtotime($this->message->data->referenceSchedule)),
                    $sportId,
                    $masterLeagueId,
                    $this->message->data->events[0]->eventId
                ]);
            }

            $updatedOdds = [];

            $toInsert['Event']['data'] = [
                'sport_id'         => $sportId,
                'provider_id'      => $providerId,
                'event_identifier' => $this->message->data->events[0]->eventId,
                'league_id'        => $leagueId,
                'team_home_id'     => $teamHomeId,
                'team_away_id'     => $teamAwayId,
                'ref_schedule'     => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                'deleted_at'       => null,
                'missing_count'    => 0
            ];

            $subTasks['event-raw'] = $toInsert;

            if (!empty($eventId)) {
                $this->dbOptions['is-event-new'] = false;
            }

            $arrayEvents = $this->message->data->events;
            $counter     = 0;

            $toInsert['MasterEvent']['swtKey'] = $eventSwtId;
            $toInsert['MasterEvent']['data']   = [
                'sport_id'               => $sportId,
                'master_event_unique_id' => $uid,
                'master_league_id'       => $masterLeagueId,
                'master_team_home_id'    => $masterTeamHomeId,
                'master_team_away_id'    => $masterTeamAwayId,
                'ref_schedule'           => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                'game_schedule'          => $this->message->data->schedule,
                'score'                  => $this->message->data->home_score . " - " . $this->message->data->away_score,
                'running_time'           => $this->message->data->runningtime,
                'home_penalty'           => $this->message->data->home_redcard,
                'away_penalty'           => $this->message->data->away_redcard,
                'deleted_at'             => null
            ];
            $subTasks['event']                 = $toInsert;

            foreach ($arrayEvents as $keyEvent => $event) {
                if (!empty($event)) {
                    foreach ($event->market_odds as $columns) {
                        if (empty($columns->marketSelection)) {
                            $this->dbOptions['event-only'] = false;
                            break 2;
                        }

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

                        $doesExist = false;
                        foreach ($oddTypesTable as $oddTypeKey => $oddType) {
                            if (strpos($oddTypeKey, $oddTypeSwtId) === 0) {
                                $doesExist = true;
                                break;
                            }
                        }

                        if ($doesExist) {
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

                        $doesExist = false;
                        foreach ($sportOddTypesTable as $sportOddTypeKey => $sportOddType) {
                            if (strpos($sportOddTypeKey, $sportOddTypeSwtId) === 0) {
                                $doesExist = true;
                                break;
                            }
                        }

                        if (!$doesExist) {
                            throw new Exception("Sport Odds Type doesn't exist");
                        }

                        /** loop each `marketSelection` from each `market_odds` */
                        foreach ($columns->marketSelection as $markets) {
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
                                    $marketOdds = $marketOdds[0];
                                }
                            }

                            $marketOdds = trim($marketOdds) == '' ? 0 : (float) $marketOdds;

                            if (array_key_exists('points', $markets)) {
                                $marketPoints = $markets->points;
                            }

                            if ($markets->market_id == "") {
                                $subTasks['remove-event-market'][] = [
                                    'odd_type_id'      => $oddTypeId,
                                    'provider_id'      => $providerId,
                                    'market_event_identifier' => $event->eventId,
                                    'market_flag'      => strtoupper($markets->indicator),
                                ];

                                continue;
                            }

                            $masterEventMarketSwtId = implode(':', [
                                "pId:" . $providerId,
                                "meUID:" . $uid,
                                "bId:" . $markets->market_id
                            ]);

                            $isMarketSame = true;

                            $start = microtime(true);

                            $memUID = null;
                            $odds = null;
                            $doesExist = false;
                            foreach ($eventMarketsTable as $eventMarketKey => $eventMarket) {
                                if (strpos($eventMarketKey, $masterEventMarketSwtId) === 0) {
                                    $memUID = $eventMarket['master_event_market_unique_id'];
                                    $odds   = $eventMarket['odds'];
                                    $doesExist = true;
                                    break;
                                }
                            }

                            $end = microtime(true);
                            Log::debug('ODDS TRANSFORMATION START TIME -> ' . $start);
                            Log::debug('ODDS TRANSFORMATION END TIME -> ' . $end);
                            Log::debug('ODDS TRANSFORMATION RUN TIME -> ' . ($end - $start));

                            if (Redis::exists($masterEventMarketSwtId)) {
                                $redis = json_decode(Redis::get($masterEventMarketSwtId), true);
                                $memUID = $redis['master_event_market_unique_id'];
                                $odds = $redis['odds'];

                                if ($odds != $marketOdds) {
                                    $eventMarketsTable[$masterEventMarketSwtId]['odds'] = $marketOdds;
                                    $this->updated                                      = true;
                                    $oddsUpdated                                        = ['market_id'        => $memUID,
                                                                                           'odds'             => $marketOdds,
                                                                                           'provider_id'      => $providerId,
                                                                                           'event_identifier' => $event->eventId
                                    ];
                                    if (!empty($marketPoints)) {
                                        $oddsUpdated['points'] = $marketPoints;
                                    }
                                    $updatedOdds[] = $oddsUpdated;
                                    $isMarketSame  = false;
                                }
                            } else {
                                $memUID       = null;
                                $isMarketSame = false;
                            }

                            /** TO INSERT */
                            $toInsert['MasterEventMarket']['swtKey'] = $masterEventMarketSwtId;
                            $toInsert['MasterEventMarket']['data']   = [
                                // 'master_event_unique_id'        => $uid,
                                'odd_type_id'                   => $oddTypeId,
                                'master_event_market_unique_id' => $memUID,
                                'is_main'                       => $event->market_type == 1 ? true : false,
                                'market_flag'                   => strtoupper($markets->indicator),
                            ];

                            $toInsert['EventMarket']['data'] = [
                                'provider_id'             => $providerId,
                                // 'master_event_unique_id'  => $uid,
                                'odd_type_id'             => $oddTypeId,
                                'odds'                    => $marketOdds,
                                'odd_label'               => $marketPoints,
                                'bet_identifier'          => $markets->market_id,
                                'is_main'                 => $event->market_type == 1 ? true : false,
                                'market_flag'             => strtoupper($markets->indicator),
                                'market_event_identifier' => $event->eventId,
                                'deleted_at'              => null,
                                // 'game_schedule'           => $this->message->data->schedule,
                            ];

                            if (!$isMarketSame) {
                                $toInsert['MasterEventMarketLog']['data'] = [
                                    'provider_id' => $providerId,
                                    'odd_type_id' => $oddTypeId,
                                    'odds'        => $marketOdds,
                                    'odd_label'   => $marketPoints,
                                    'is_main'     => $event->market_type == 1 ? true : false,
                                    'market_flag' => strtoupper($markets->indicator),
                                ];
                            }

                            $subTasks['event-market'][] = $toInsert;
                        }
                    }
                }
                $counter++;
            }

            $subTasks['updated-odds'] = [];
            if ($this->updated) {
                $subTasks['updated-odds'] = $updatedOdds;
            }

            if (!empty($subTasks['event'])) {
                Log::info('Transformation - finished, continue to saving');
                $transformKafkaMessageOddsSaveToDb = resolve('TransformKafkaMessageOddsSaveToDb');
                Task::deliver($transformKafkaMessageOddsSaveToDb->init($subTasks, $uid, $this->dbOptions));
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getLine());
        }
    }

    private function saveLeaguesData($swoole, $providerId, $sportId, $leagueName)
    {
        /**
         * Check if league exist in leagues records
         */
        $doesExist = false;
        foreach ($swoole->rawLeaguesTable as $key => $value) {
            if ($sportId == $value['sport_id'] &&
                $providerId == $value['provider_id'] &&
                $leagueName == $value['name']
            ) {
                $leagueId = $value['id'];
                $doesExist = true;
                break;
            }
        }

        if (!$doesExist) {
            $league = League::withTrashed()->updateOrCreate([
                'sport_id'    => $sportId,
                'provider_id' => $providerId,
                'name'        => $leagueName
            ], []);

            $leagueId = $league->id;

            $swoole->rawLeaguesTable->set('leagueId:' . $league->id, [
                'id' => $league->id,
                'sport_id'    => $sportId,
                'provider_id' => $providerId,
                'name' => $league->name
            ]);
        }

        return $leagueId;
    }

    private function saveTeamsData($swoole, $providerId,  $sportId, $team1, $team2)
    {
        $team = ['home' => new stdClass(), 'away' => new stdClass()];
        /**
         * Check if team exist in teams records
         */
        $doesExist = false;
        foreach ($swoole->rawTeamsTable as $key => $value) {
            if ($sportId == $value['sport_id'] &&
                $providerId == $value['provider_id'] &&
                $team1 == $value['name']
            ) {
                $team['home']->id = $value['id'];
                $doesExist = true;
                break;
            }
        }
        if (!$doesExist) {
            $team['home'] = Team::withTrashed()->updateOrCreate([
                'sport_id'    => $sportId,
                'name'        => $team1,
                'provider_id' => $providerId,
            ], []);

            $swoole->rawTeamsTable->set('teamId:' . $team['home']->id, [
                'id'          => $team['home']->id,
                'sport_id'    => $sportId,
                'provider_id' => $providerId,
                'name'        => $team['home']->name
            ]);
        }

        /**
         * Check if team exist in teams records
         */
        $doesExist = false;
        foreach ($swoole->rawTeamsTable as $key => $value) {
            if ($sportId == $value['sport_id'] &&
                $providerId == $value['provider_id'] &&
                $team2 == $value['name']
            ) {
                $team['away']->id = $value['id'];
                $doesExist = true;
                break;
            }
        }
        if (!$doesExist) {
            $team['away'] = Team::withTrashed()->updateOrCreate([
                'sport_id'    => $sportId,
                'name'        => $team2,
                'provider_id' => $providerId
            ], []);

            $swoole->rawTeamsTable->set('teamId:' . $team['away']->id, [
                'id'          => $team['away']->id,
                'sport_id'    => $sportId,
                'provider_id' => $providerId,
                'name'        => $team['away']->name
            ]);
        }

        return $team;
    }
}
