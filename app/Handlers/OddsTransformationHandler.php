<?php

namespace App\Handlers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use App\Tasks\TransformKafkaMessageOddsSaveToDb;
use Exception;

class OddsTransformationHandler
{
    protected $message;
    protected $internalParameters;
    protected $updated   = false;
    protected $uid       = null;
    protected $dbOptions = [
        'event-only'   => true,
        'is-event-new' => true
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

    public function __construct($message, $internalParameters)
    {
        $this->message            = $message;
        $this->internalParameters = $internalParameters;
    }

    public function handle()
    {
        try {
            $swoole                                   = app('swoole');
            $toInsert                                 = [];
            $subTasks['remove-previous-market']       = [];

            /** DATABASE TABLES */
            /** LOOK-UP TABLES */
            $providersTable                           = $swoole->providersTable;
            $sportsTable                              = $swoole->sportsTable;
            $leaguesTable                             = $swoole->leaguesTable;
            $teamsTable                               = $swoole->teamsTable;
            $eventsTable                              = $swoole->eventsTable;
            $oddTypesTable                            = $swoole->oddTypesTable;
            $sportOddTypesTable                       = $swoole->sportOddTypesTable;
            $eventMarketsTable                        = $swoole->eventMarketsTable;
            $leagueLookUpTable                        = $swoole->leagueLookUpTable;
            $teamLookUpTable                          = $swoole->teamLookUpTable;
            $eventScheduleChangeTable                 = $swoole->eventScheduleChangeTable;

            list('providerId'       => $providerId,
                'sportId'           => $sportId,
                'multiLeagueId'     => $multiLeagueId,
                'masterLeagueName'  => $masterLeagueName,
                'multiTeam'         => $multiTeam,
                'isLeagueSelected'  => $isLeagueSelected,
                'leagueId'          => $leagueId) = $this->internalParameters;

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

                $doesExist = false;
                foreach ($eventsTable as $key => $value) {
                    if (
                        $sportId == $value['sport_id'] && 
                        $multiTeam['home']['id'] == $value['master_team_home_id'] &&
                        $multiTeam['away']['id'] == $value['master_team_away_id'] &&
                        $this->message->data->schedule == $value['game_schedule']
                    ) {
                        $eventSwtId = $key;
                        $eventsData = $value;
                        $doesExist = true;
                    }
                }
                if ($doesExist) {
                    $eventId        = $eventsData['id'];
                    $uid            = $eventsData['master_event_unique_id'];

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
                        $subTasks['remove-previous-market'][] = [
                            'uid'    => $uid,
                            'swtKey' => implode(':', [
                                "pId:" . $providerId,
                                "meUID:" . $uid,
                            ]),
                        ];

                        $eventScheduleChangeTable->set('eventScheduleChange:' . $uid, [
                            'value' => json_encode([
                                'uid'           => $uid,
                                'game_schedule' => $this->message->data->schedule,
                                'sport_id'      => $sportId
                            ])
                        ]);

                        $eventsTable[$eventSwtId]['game_schedule'] = $this->message->data->schedule;
                    }
                } else {
                    $masterTeamHomeId = $multiTeam['home']['id'];
                    $masterTeamAwayId = $multiTeam['away']['id'];

                    $teamHomeId = $multiTeam['home']['raw_id'];
                    $teamAwayId = $multiTeam['away']['raw_id'];

                    $uid = implode('-', [
                        date("Ymd", strtotime($this->message->data->referenceSchedule)),
                        $sportId,
                        $multiLeagueId,
                        $this->message->data->events[0]->eventId
                    ]);
                }
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
                'deleted_at'       => null
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
                'master_league_id'       => $multiLeagueId,
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
            $subTasks['event']           = $toInsert;

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
                            "sId:" . $sportId,
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
                            $emptyMarket  = false;

                            if (gettype($marketOdds) == 'string') {
                                $marketOdds = explode(' ', $markets->odds);

                                if (count($marketOdds) > 1) {
                                    $marketPoints = $marketOdds[0];
                                    $marketOdds   = $marketOdds[1];
                                } else {
                                    $marketOdds = $marketOdds[0];
                                }
                            }

                            $marketOdds = trim($marketOdds) == '' ? 0 : (float)$marketOdds;

                            if (array_key_exists('points', $markets)) {
                                $marketPoints = $markets->points;
                            }

                            if ($markets->market_id == "") {
                                $marketOdds = 0;

                                $subTasks['remove-event-market'][] = [
                                    'odd_type_id'      => $oddTypeId,
                                    'provider_id'      => $providerId,
                                    'event_identifier' => $event->eventId,
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
                            if ($eventMarketsTable->exist($masterEventMarketSwtId) && !empty($markets->market_id)) {
                                $memUID = $eventMarketsTable->get($masterEventMarketSwtId)['master_event_market_unique_id'];
                                $odds   = $eventMarketsTable->get($masterEventMarketSwtId)['odds'];

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
                                $memUID       = uniqid();
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
                    Task::deliver(new TransformKafkaMessageOddsSaveToDb($subTasks, $uid, $this->dbOptions));
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
