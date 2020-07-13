<?php

namespace App\Handlers;

use App\Models\{Game, League, Team};
use Illuminate\Support\Facades\{DB, Log, Redis};
use Illuminate\Support\Str;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Exception;
use App\Facades\SwooleHandler;

class OddsTransformationHandler
{
    protected $message;
    protected $internalParameters;
    protected $updated   = false;
    protected $uid       = null;
    protected $dbOptions = [
        'event-only'    => false,
        'is-event-new'  => true,
        'in-masterlist' => true
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

    const HG = 1;

    public function init($message, $internalParameters)
    {
        $this->message            = $message;
        $this->internalParameters = $internalParameters;
        return $this;
    }

    public function handle()
    {
        try {
            $swoole   = app('swoole');
            $toInsert = [];
            $subTasks = [];

            /** DATABASE TABLES */
            /** LOOK-UP TABLES */
            $eventRecordsTable        = $swoole->eventRecordsTable;
            $oddTypesTable            = $swoole->oddTypesTable;
            $sportOddTypesTable       = $swoole->sportOddTypesTable;
            $eventMarketsTable        = $swoole->eventMarketsTable;
            $eventScheduleChangeTable = $swoole->eventScheduleChangeTable;

            list(
                'providerId' => $providerId,
                'sportId' => $sportId,
                'parameters' => $parameters
                ) = $this->internalParameters;

            list(
                'master_league_id' => $masterLeagueId,
                'master_team_home_id' => $multiTeam['home']['id'],
                'master_team_away_id' => $multiTeam['away']['id'],
                'league_id' => $leagueId,
                'team_home_id' => $teamHomeId,
                'team_away_id' => $teamAwayId,
                'master_league_name' => $masterLeagueName,
                'master_team_home_name' => $multiTeam['home']['name'],
                'master_team_away_name' => $multiTeam['away']['name']
                ) = $parameters;

            /**
             * EVENTS (MASTER) Swoole Table
             *
             * @ref config.laravels.events
             *
             * @var $arrayEvents  array               Contains Event information extracted from game data json
             *      $eventRecordsTable  swoole_table
             *      $eventSwtId   swoole_table_key    "sId:<$sportId>:masterLeagueId:<$masterLeagueId>:eId:<$rawEventId>"
             *      $event        swoole_table_value  string
             */
            $eventSwtId = implode(':', [
                "sId:" . $sportId,
                "pId:" . $providerId,
                "eventIdentifier:" . $this->message->data->events[0]->eventId
            ]);

            $eventRecord = SwooleHandler::getValue('eventRecordsTable', $eventSwtId);

            if ($eventRecord) {
                $eventsData = json_decode($eventRecord['raw_data'], true);

                $mlEventRecord = SwooleHandler::getValue('mlEventsTable', implode(':', [
                    $sportId,
                    $masterLeagueId,
                    $multiTeam['home']['id'],
                    $multiTeam['away']['id'],
                    date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule))
                ]));

                if ($mlEventRecord) {
                    $uid = $mlEventRecord['master_event_unique_id'];
                } else {
                    $uid = implode('-', [
                        date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                        $sportId,
                        $masterLeagueId,
                        $this->message->data->events[0]->eventId
                    ]);
                }

//                $eventId = $eventsData['id'];
//                $uid     = $eventsData['master_event_unique_id'];
//
//                $masterTeamHomeId = $eventsData['master_team_home_id'];
//                $masterTeamAwayId = $eventsData['master_team_away_id'];
//
//                $teamHomeId = $eventsData['team_home_id'];
//                $teamAwayId = $eventsData['team_away_id'];

                if ($this->message->data->schedule == 'early' && $eventsData['schedule'] == 'today') {
                    Log::info("Transformation ignored - event is already in today");

                    $this->saveEventRecords($eventSwtId, $sportId, $leagueId, $teamHomeId, $teamAwayId, $providerId);
                    return;
                }

                if ($this->message->data->schedule == 'today' && $eventsData['schedule'] == 'inplay') {
                    Log::info("Transformation ignored - event is already in play");

                    $this->saveEventRecords($eventSwtId, $sportId, $leagueId, $teamHomeId, $teamAwayId, $providerId);
                    return;
                }

                $this->saveEventRecords($eventSwtId, $sportId, $leagueId, $teamHomeId, $teamAwayId, $providerId);

                $arrayEvents = $this->message->data->events;
                foreach ($arrayEvents as $event) {
                    foreach ($event->market_odds as $marketOdd) {
                        if (empty($marketOdd->marketSelection)) {
//                            $this->dbOptions['event-only'] = false;
                            break 2;
                        }
                        foreach ($marketOdd->marketSelection as $marketSelection) {
                            if (empty($marketSelection->market_id)) {
                                break 3;
                            }
                            $odds   = $marketSelection->odds;
                            $points = "";

                            if (gettype($odds) == 'string') {
                                $odds = explode(' ', $marketSelection->odds);

                                if (count($odds) > 1) {
                                    $points = $odds[0];
                                    $odds   = $odds[1];
                                } else {
                                    $odds = $odds[0];
                                }
                            }

                            $odds = trim($odds) == '' ? 0 : (float) $odds;

                            $oddRecord = SwooleHandler::getValue('oddRecordsTable', 'sId:' . $sportId . ':pId:' . $providerId . ':marketId:' . $marketSelection->market_id);

                            Log::debug('sId:' . $sportId . ':pId:' . $providerId . ':marketId:' . $marketSelection->market_id);
                            if ($oddRecord) {
                                if ($oddRecord['odds'] != $odds) {
                                    $oddsUpdated = [
                                        'market_id'   => $oddRecord['memUID'],
                                        'odds'        => $odds,
                                        'provider_id' => $providerId,
                                        //                                    'event_identifier' => $this->message->data->events[0]->eventId
                                    ];
                                    if (!empty($marketPoints)) {
                                        $oddsUpdated['points'] = $points;
                                    }
                                    $updatedOdds[] = $oddsUpdated;
                                }
                            }
                        }
                    }
                }

//                foreach ($arrayEvents as $keyEvent => $event) {
//                    if (!empty($event)) {
//                        foreach ($event->market_odds as $columns) {
//                            if (empty($columns->marketSelection)) {
//                                $this->dbOptions['event-only'] = false;
//                                break 2;
//                            }
//
//                            /**
//                             * ODD TYPES Swoole Table
//                             *
//                             * @ref config.laravels.oddType
//                             *
//                             * @var $oddTypesTable  swoole_table
//                             *      $oddTypeSwtId   swoole_table_key    "oddType:<$events[]->market_odds[]->oddsType>"
//                             *      $oddTypeId      swoole_table_value  int
//                             */
//                            $oddTypesData = SwooleHandler::getValue('oddTypesTable', "oddType:" . $columns->oddsType);
//                            if ($oddTypesData) {
//                                $oddTypeId = $oddTypesData['id'];
//                            } else {
//                                throw new Exception("Odds Type doesn't exist");
//                            }
//
//                            /**
//                             * SPORT ODD TYPES Swoole Table
//                             *
//                             * @ref config.laravels.sportOddType
//                             *
//                             * @var $sportOddTypesTable  swoole_table
//                             *      $sportOddTypeSwtId   swoole_table_key    "sId:<$sportId>:oddTypeId:<$oddTypeId>"
//                             *      $sportOddTypeId      swoole_table_value  int
//                             */
//                            $sportOddTypeSwtId = implode(':', [
//                                "sId:" . $sportId,
//                                "oddType:" . Str::slug($columns->oddsType)
//                            ]);
//
//                            $sportsOddsTypeData = SwooleHandler::getValue('sportOddTypesTable', $sportOddTypeSwtId);
//                            if (!$sportsOddsTypeData) {
//                                throw new Exception("Sport Odds Type doesn't exist");
//                            }
//
//                            /** loop each `marketSelection` from each `market_odds` */
//                            foreach ($columns->marketSelection as $markets) {
//                                /**
//                                 * EVENT MARKETS (MASTER) Swoole Table
//                                 *
//                                 * @ref config.laravels.eventMarkets
//                                 *
//                                 * @var $eventMarketsTable  swoole_table
//                                 *      $eventMarketSwtId   swoole_table_key    "pId:<$providerId>:meUniqueId:<$masterEventUniqueId>:memUniqueId:<$masterEventMarketUniqueId>"
//                                 *      $eventMarket        swoole_table_value  string
//                                 */
//
//                                $marketOdds   = $markets->odds;
//                                $marketPoints = "";
//
//                                if (gettype($marketOdds) == 'string') {
//                                    $marketOdds = explode(' ', $markets->odds);
//
//                                    if (count($marketOdds) > 1) {
//                                        $marketPoints = $marketOdds[0];
//                                        $marketOdds   = $marketOdds[1];
//                                    } else {
//                                        $marketOdds = $marketOdds[0];
//                                    }
//                                }
//
//                                $marketOdds = trim($marketOdds) == '' ? 0 : (float) $marketOdds;
//
//                                if (array_key_exists('points', $markets)) {
//                                    $marketPoints = $markets->points;
//                                }
//
//                                if ($markets->market_id == "") {
//                                    $subTasks['remove-event-market'][] = [
//                                        'odd_type_id'             => $oddTypeId,
//                                        'provider_id'             => $providerId,
//                                        'market_event_identifier' => $event->eventId,
//                                        'market_flag'             => strtoupper($markets->indicator),
//                                    ];
//
//                                    continue;
//                                }
//
//                                $masterEventMarketSwtId = implode(':', [
//                                    "pId:" . $providerId,
//                                    "meUID:" . $uid,
//                                    "bId:" . $markets->market_id
//                                ]);
//
//                                $isMarketSame = true;
//                                $eventMarkets = $eventMarketsTable[$masterEventMarketSwtId];
//
//                                if (!empty($eventMarkets)) {
//                                    $memUID = $eventMarkets['master_event_market_unique_id'];
//                                    $odds   = $eventMarkets['odds'];
//
//                                    if ($odds != $marketOdds) {
//                                        $eventMarketsTable[$masterEventMarketSwtId]['odds'] = $marketOdds;
//                                        $this->updated                                      = true;
//                                        $oddsUpdated                                        = ['market_id'        => $memUID,
//                                                                                               'odds'             => $marketOdds,
//                                                                                               'provider_id'      => $providerId,
//                                                                                               'event_identifier' => $event->eventId
//                                        ];
//                                        if (!empty($marketPoints)) {
//                                            $oddsUpdated['points'] = $marketPoints;
//                                        }
//                                        $updatedOdds[] = $oddsUpdated;
//                                        $isMarketSame  = false;
//                                    }
//                                } else {
//                                    $memUID       = null;
//                                    $isMarketSame = false;
//                                }
//
//                                /** TO INSERT */
//                                $toInsert['MasterEventMarket']['swtKey'] = $masterEventMarketSwtId;
//                                $toInsert['MasterEventMarket']['data']   = [
//                                    // 'master_event_unique_id'        => $uid,
//                                    'odd_type_id'                   => $oddTypeId,
//                                    'master_event_market_unique_id' => $memUID,
//                                    'is_main'                       => $event->market_type == 1 ? true : false,
//                                    'market_flag'                   => strtoupper($markets->indicator),
//                                ];
//
//                                $toInsert['EventMarket']['data'] = [
//                                    'provider_id'             => $providerId,
//                                    // 'master_event_unique_id'  => $uid,
//                                    'odd_type_id'             => $oddTypeId,
//                                    'odds'                    => $marketOdds,
//                                    'odd_label'               => $marketPoints,
//                                    'bet_identifier'          => $markets->market_id,
//                                    'is_main'                 => $event->market_type == 1 ? true : false,
//                                    'market_flag'             => strtoupper($markets->indicator),
//                                    'market_event_identifier' => $event->eventId,
//                                    'deleted_at'              => null,
//                                    // 'game_schedule'           => $this->message->data->schedule,
//                                ];
//
//                                if (!$isMarketSame) {
//                                    $toInsert['MasterEventMarketLog']['data'] = [
//                                        'provider_id' => $providerId,
//                                        'odd_type_id' => $oddTypeId,
//                                        'odds'        => $marketOdds,
//                                        'odd_label'   => $marketPoints,
//                                        'is_main'     => $event->market_type == 1 ? true : false,
//                                        'market_flag' => strtoupper($markets->indicator),
//                                    ];
//                                }
//
//                                $subTasks['event-market'][] = $toInsert;
//                            }
//                        }
//                    }
//                    $counter++;
//                }
            } else {
                $mlEventRecord = SwooleHandler::getValue('mlEventsTable', implode(':', [
                    $sportId,
                    $masterLeagueId,
                    $multiTeam['home']['id'],
                    $multiTeam['away']['id'],
                    date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule))
                ]));

                if ($mlEventRecord) {
                    $uid = $mlEventRecord['master_event_unique_id'];
                } else {
                    $uid = implode('-', [
                        date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                        $sportId,
                        $masterLeagueId,
                        $this->message->data->events[0]->eventId
                    ]);

                    SwooleHandler::setValue('mlEventsTable', implode(':', [
                        $sportId,
                        $masterLeagueId,
                        $multiTeam['home']['id'],
                        $multiTeam['away']['id'],
                        date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule))
                    ]), [
                        'master_event_unique_id' => $uid
                    ]);
                }

                $this->saveEventRecords($eventSwtId, $sportId, $leagueId, $teamHomeId, $teamAwayId, $providerId);

                $getEvents = [
                    'uid'            => $uid,
                    'game_schedule'  => $this->message->data->schedule,
                    'has_bet'        => false,
                    'home'           => [
                        'name'    => $multiTeam['home']['name'],
                        'redcard' => $this->message->data->home_redcard,
                        'score'   => $this->message->data->home_score
                    ],
                    'away'           => [
                        'name'    => $multiTeam['away']['name'],
                        'redcard' => $this->message->data->away_redcard,
                        'score'   => $this->message->data->away_score
                    ],
                    'league_name'    => $masterLeagueName,
                    'with_providers' => [['id' => $providerId, 'provider' => strtoupper($this->message->data->provider)]],
                    'ref_schedule'   => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                    'running_time'   => $this->message->data->runningtime,
                    'sport'          => SwooleHandler::getValue('sportsTable', 'sId:' . $sportId)['sport'],
                    'sport_id'       => $sportId
                ];

                $mainMarkets = $this->message->data->events[0];
                foreach ($mainMarkets->market_odds as $marketOdds) {
                    foreach ($marketOdds->marketSelection as $marketSelection) {
                        $indicator = strtoupper($marketSelection->indicator);
                        if (in_array($indicator, ['OVER', 'UNDER'])) {
                            if ($indicator == 'OVER') {
                                $indicator = 'HOME';
                            } else {
                                $indicator = 'AWAY';
                            }
                        }
                        $odds   = $marketSelection->odds;
                        $points = "";

                        if (gettype($marketOdds) == 'string') {
                            $marketOdds = explode(' ', $marketSelection->odds);

                            if (count($odds) > 1) {
                                $points = $odds[0];
                                $odds   = $odds[1];
                            } else {
                                $odds = $odds[0];
                            }
                        }

                        $odds                                                                        = trim($odds) == '' ? 0 : (float) $odds;
                        $getEvents['market_odds']['main'][$marketOdds->oddsType][$indicator]['odds'] = $odds;
                        if (array_key_exists('points', $marketSelection)) {
                            $getEvents['market_odds']['main'][$marketOdds->oddsType][$indicator]['points'] = $points;
                        }

                        if (!Redis::exists($marketSelection->market_id)) {
                            $memUID = md5($this->offset . uniqid(rand(10000, 99999), true) . $indicator . $marketSelection->market_id, '');
                            Redis::set($marketSelection->market_id, $memUID);
                        } else {
                            $memUID = Redis::get($marketSelection->market_id);
                        }

                        $getEvents['market_odds']['main'][$marketOdds->oddsType][$indicator]['market_id']      = $memUID;
                        $getEvents['market_odds']['main'][$marketOdds->oddsType][$indicator]['provider_alias'] = strtoupper($this->message->data->provider);

                        SwooleHandler::setValue('oddRecordsTable', 'sId:' . $sportId . ':pId:' . $providerId . ':marketId:' . $marketSelection->market_id, [
                            'market_id'   => $marketSelection->market_id,
                            'sport_id'    => $sportId,
                            'provider_id' => $providerId,
                            'odds'        => $odds,
                            'memUID'      => $memUID
                        ]);
                    }
                }
                foreach ($swoole->userSelectedLeaguesTable as $userSelectedLeague) {
                    if (
                        $userSelectedLeague['league_name'] == $masterLeagueName &&
                        $userSelectedLeague['sport_id'] == $sportId &&
                        $userSelectedLeague['schedule'] == $this->message->data->schedule
                    ) {
                        $userId = $userSelectedLeague['user_id'];
                        $fd     = SwooleHandler::getValue('wsTable', 'uid:' . $userId);
                        $swoole->push($fd['value'], json_encode([
                            'getAdditionalEvents' => [$getEvents]
                        ]));
                    }
                }
            }
            if (!empty($updatedOdds)) {

                $swoole->updatedEventsTable->set("updatedEvents:" . $uid, ['value' => json_encode($updatedOdds)]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error(json_encode(
                [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file'    => $e->getFile(),
                ]
            ));
        }
    }

    private function saveEventRecords($eventSwtId, $sportId, $leagueId, $teamHomeId, $teamAwayId, $providerId)
    {
        $this->message->data->id = null;

        SwooleHandler::setValue('eventRecordsTable', $eventSwtId, [
            'event_identifier' => $this->message->data->events[0]->eventId,
            'sport_id'         => $sportId,
            'league_id'        => $leagueId,
            'team_home_id'     => $teamHomeId,
            'team_away_id'     => $teamAwayId,
            'ref_schedule'     => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
            'provider_id'      => $providerId,
            'raw_data'         => json_encode($this->message->data)
        ]);
    }
}
