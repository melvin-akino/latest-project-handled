<?php

namespace App\Tasks;

use App\Facades\SwooleHandler;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\{Log, Redis};

class TransformKafkaMessageOdds extends Task
{
    protected $message;
    protected $offset;
    protected $internalParameters;
    protected $uid = null;

    const REDIS_TTL = 60 * 60 * 24;

    public function init($offset, $internalParameters)
    {
        $message                  = SwooleHandler::getValue('oddsKafkaPayloadsTable', $offset);
        $this->message            = json_decode($message['message']);
        $this->internalParameters = $internalParameters;
        $this->offset             = $offset;

        SwooleHandler::remove('oddsKafkaPayloadsTable', $offset);

        $toLogs = [
            "class"       => "TransformKafkaMessageOdds",
            "message"     => "Initiating...",
            "module"      => "TASK",
            "status_code" => 102,
        ];
        monitorLog('monitor_tasks', 'info', $toLogs);

        return $this;
    }

    public function handle()
    {
        Log::info("Starting Task for offset:" . $this->offset);
        try {
            $startTime = microtime(TRUE);

            list(
                'providerId' => $providerId,
                'sportId'    => $sportId,
                'parameters' => $parameters,
                'withChange' => $withChange
                ) = $this->internalParameters;

            list(
                'master_league_id'      => $masterLeagueId,
                'master_team_home_id'   => $multiTeam['home']['id'],
                'master_team_away_id'   => $multiTeam['away']['id'],
                'league_id'             => $leagueId,
                'team_home_id'          => $teamHomeId,
                'team_away_id'          => $teamAwayId,
                'master_league_name'    => $masterLeagueName,
                'master_team_home_name' => $multiTeam['home']['name'],
                'master_team_away_name' => $multiTeam['away']['name']
                ) = $parameters;

            if (!SwooleHandler::exists('providerEventsTable', $this->message->data->events[0]->eventId)) {
                Log::info("Transformation ignored - event is not yet recorded or not in a current trade window display");
                return;
            }

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

            $getEvents = [
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

            $updatedOdds = [];
            if ($eventRecord) {
                $mlEventRecord = SwooleHandler::getValue('mlEventsTable', implode(':', [
                    $sportId,
                    $masterLeagueId,
                    $multiTeam['home']['id'],
                    $multiTeam['away']['id'],
                    date("Y-m-d", strtotime($this->message->data->referenceSchedule))
                ]));

                if ($mlEventRecord) {
                    $uid = $mlEventRecord['master_event_unique_id'];
                } else {
                    $uid = implode('-', [
                        date("Ymd", strtotime($this->message->data->referenceSchedule)),
                        $sportId,
                        $masterLeagueId,
                        $this->message->data->events[0]->eventId
                    ]);
                }

                $getEvents['uid'] = $uid;

                if ($this->message->data->schedule == 'early' && $eventRecord['game_schedule'] == 'today') {
                    Log::info("Transformation ignored - event is already in today");

                    $this->saveEventRecords($eventSwtId, $sportId, $leagueId, $teamHomeId, $teamAwayId, $providerId, $uid, $masterLeagueName);
                    return;
                }

                if ($this->message->data->schedule == 'today' && $eventRecord['game_schedule'] == 'inplay') {
                    Log::info("Transformation ignored - event is already in play");

                    $this->saveEventRecords($eventSwtId, $sportId, $leagueId, $teamHomeId, $teamAwayId, $providerId, $uid, $masterLeagueName);
                    return;
                }

                $this->saveEventRecords($eventSwtId, $sportId, $leagueId, $teamHomeId, $teamAwayId, $providerId, $uid, $masterLeagueName);

                if (SwooleHandler::exists('eventHasMarketsTable', 'eventHasMarkets:' . $uid)) {
                    SwooleHandler::setColumnValue('eventHasMarketsTable', 'eventHasMarkets:' . $uid, 'has_markets', 1);
                }

                if ($withChange) {
                    $updateLeagues = true;
                    $arrayEvents = $this->message->data->events;
                    foreach ($arrayEvents as $eventKey => $event) {
                        foreach ($event->market_odds as $marketOdd) {
                            if (empty($marketOdd->marketSelection)) { // means that one of the team scored
                                SwooleHandler::setValue('eventsScoredTable', 'eventsScored:' . $uid, [
                                    'uid'              => $uid,
                                    'master_league_id' => $masterLeagueId,
                                    'schedule'         => $this->message->data->schedule,
                                    'sport_id'         => $sportId
                                ]);
                                SwooleHandler::setValue('eventHasMarketsTable', 'eventHasMarkets:' . $uid, [
                                    'uid'              => $uid,
                                    'master_league_id' => $masterLeagueId,
                                    'schedule'         => $this->message->data->schedule,
                                    'sport_id'         => $sportId,
                                    'has_markets'      => 0
                                ]);
                                Log::info($uid . " event scored");
                                break 2;
                            }
                            foreach ($marketOdd->marketSelection as $marketSelection) {
                                if (empty($marketSelection->market_id)) {// means that the event no longer have market id for a specific type

                                    if (in_array($marketOdd->oddsType, ['1X2', 'HT 1X2']) && $event->market_type != 1) {
                                        break 2;
                                    }
                                    SwooleHandler::setValue('eventNoMarketIdsTable', 'market_event_identifier:' . $event->eventId, [
                                        'uid'                     => $uid,
                                        'odd_type'                => $marketOdd->oddsType,
                                        'market_event_identifier' => $event->eventId,
                                        'master_league_id'        => $masterLeagueId,
                                        'schedule'                => $this->message->data->schedule,
                                        'sport_id'                => $sportId
                                    ]);
                                    Log::info($uid . " event no market for type " . $marketOdd->oddsType . ' for market event identifier ' . $event->eventId);


                                    break;
                                }
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
                                if ($eventKey == 0) {
                                    $getEvents['market_odds']['main'][$marketOdd->oddsType][$indicator]['odds'] = $odds;
                                }

                                if (array_key_exists('points', $marketSelection)) {
                                    $points = $marketSelection->points;
                                    if ($eventKey == 0) {
                                        $getEvents['market_odds']['main'][$marketOdd->oddsType][$indicator]['points'] = $points;
                                    }
                                }

                                $oddRecord = SwooleHandler::getValue('oddRecordsTable', 'sId:' . $sportId . ':pId:' . $providerId . ':marketId:' . $marketSelection->market_id);

                                if ($eventKey == 0) {
                                    $getEvents['market_odds']['main'][$marketOdd->oddsType][$indicator]['market_id']      = $oddRecord['memUID'];
                                    $getEvents['market_odds']['main'][$marketOdd->oddsType][$indicator]['provider_alias'] = strtoupper($this->message->data->provider);
                                }

                                $memUID = null;
                                if (SwooleHandler::exists('providerEventMarketsTable', $this->message->data->events[0]->eventId . ":" . $marketOdd->oddsType . $indicator . $points)) {
                                    $memUID = SwooleHandler::getValue('providerEventMarketsTable',$this->message->data->events[0]->eventId . ":" . $marketOdd->oddsType . $indicator . $points)['mem_uid'];
                                }

                                if (!empty($memUID)) {
                                    if ($oddRecord['odds'] != $odds) {
                                        $oddsUpdated = [
                                            'market_id' => $memUID,
                                            'odds'      => $odds,
                                        ];
                                        if (!empty($points)) {
                                            $oddsUpdated['points'] = $points;
                                        }
                                        $updatedOdds[] = $oddsUpdated;
                                    }

                                    SwooleHandler::setValue('oddRecordsTable', 'sId:' . $sportId . ':pId:' . $providerId . ':marketId:' . $marketSelection->market_id, [
                                        'market_id'   => $marketSelection->market_id,
                                        'sport_id'    => $sportId,
                                        'provider_id' => $providerId,
                                        'odds'        => $odds,
                                        'memUID'      => $memUID
                                    ]);
                                }
                            }
                        }
                    }
                }

            } else {
                $updateLeagues = true;
                $mlEventRecord = SwooleHandler::getValue('mlEventsTable', implode(':', [
                    $sportId,
                    $masterLeagueId,
                    $multiTeam['home']['id'],
                    $multiTeam['away']['id'],
                    date("Y-m-d", strtotime($this->message->data->referenceSchedule))
                ]));

                if ($mlEventRecord) {
                    $uid = $mlEventRecord['master_event_unique_id'];
                } else {
                    $uid = implode('-', [
                        date("Ymd", strtotime($this->message->data->referenceSchedule)),
                        $sportId,
                        $masterLeagueId,
                        $this->message->data->events[0]->eventId
                    ]);

                    SwooleHandler::setValue('mlEventsTable', implode(':', [
                        $sportId,
                        $masterLeagueId,
                        $multiTeam['home']['id'],
                        $multiTeam['away']['id'],
                        date("Y-m-d", strtotime($this->message->data->referenceSchedule))
                    ]), [
                        'master_event_unique_id' => $uid,
                    ]);
                }

                $this->saveEventRecords($eventSwtId, $sportId, $leagueId, $teamHomeId, $teamAwayId, $providerId, $uid, $masterLeagueName);

                $getEvents['uid'] = $uid;

                if ($withChange) {
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

                            if (gettype($odds) == 'string') {
                                $odds = explode(' ', $odds);

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
                                $points = $marketSelection->points;
                            }

                            $getEvents['market_odds']['main'][$marketOdds->oddsType][$indicator]['points'] = $points;

                            $memUID = null;
                            if (SwooleHandler::exists('providerEventMarketsTable', $this->message->data->events[0]->eventId . ":" . $marketOdds->oddsType . $indicator . $points)) {
                                $memUID = SwooleHandler::getValue('providerEventMarketsTable',$this->message->data->events[0]->eventId . ":" . $marketOdds->oddsType . $indicator . $points)['mem_uid'];
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

                            if (!empty($memUID)) {
                                $oddsUpdated = [
                                    'market_id' => $memUID,
                                    'odds'      => $odds,
                                ];
                                if (!empty($points)) {
                                    $oddsUpdated['points'] = $points;
                                }
                                $updatedOdds[] = $oddsUpdated;
                            }
                        }
                    }
                }
            }

            SwooleHandler::setValue('eventsInfoTable', "eventsInfo:" . $uid, [
                'value' => json_encode([
                    'id'           => $uid,
                    'score' => [
                        'home' => $this->message->data->home_score,
                        'away' => $this->message->data->away_score
                    ],
                    'running_time' => $this->message->data->runningtime,
                    'master_league_id'        => $masterLeagueId,
                    'schedule'                => $this->message->data->schedule,
                    'sport_id'                => $sportId
                ])
            ]);

            if (!empty($updatedOdds)) {
                SwooleHandler::setValue('updatedEventsTable', "updatedEvents:" . $uid, ['provider_id' => $providerId, 'odds' => json_encode($updatedOdds)]);
            }

            $endTime = microtime(TRUE);
            $timeConsumption   = $endTime - $startTime;

            $payload = [
                'request_uid'      => json_encode($this->message->request_uid),
                'request_ts'       => json_encode($this->message->request_ts),
                'offset'           => json_encode($this->offset),
                'time_consumption' => json_encode($timeConsumption),
                'gamedata'         => json_encode([
                    'leaguename' => $this->message->data->leagueName,
                    'home'       => $this->message->data->homeTeam,
                    'away'       => $this->message->data->awayTeam,
                    'schedule'   => $this->message->data->schedule,
                ]),
            ];

            $toLogs = [
                "class"       => "TransformKafkaMessageOdds",
                "message"     => $payload,
                "module"      => "TASK",
                "status_code" => 200,
            ];
            monitorLog('monitor_tasks', 'info', $toLogs);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "TransformKafkaMessageOdds",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "TASK_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_tasks', 'error', $toLogs);
        }

        Log::info("Ending Task for offset:" . $this->offset);
    }

    private function saveEventRecords($eventSwtId, $sportId, $leagueId, $teamHomeId, $teamAwayId, $providerId, $uid, $masterLeagueName)
    {
        SwooleHandler::setValue('eventRecordsTable', $eventSwtId, [
            'master_event_unique_id' => $uid,
            'event_identifier'       => $this->message->data->events[0]->eventId,
            'sport_id'               => $sportId,
            'league_id'              => $leagueId,
            'master_league_name'     => $masterLeagueName,
            'team_home_id'           => $teamHomeId,
            'team_away_id'           => $teamAwayId,
            'ref_schedule'           => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
            'game_schedule'          => $this->message->data->schedule,
            'provider_id'            => $providerId,
            'missing_count'          => 0,
        ]);

        $activeEventsSwtId = implode(':', [
            'sId:' . $sportId,
            'pId:' . $providerId,
            'schedule:' . $this->message->data->schedule
        ]);
        $activeEvents = [];
        if (SwooleHandler::exists('activeEventsTable', $activeEventsSwtId)) {
            $activeEvents = json_decode(SwooleHandler::getValue('activeEventsTable', $activeEventsSwtId)['events'], true);
        }
        if (!in_array($this->message->data->events[0]->eventId, $activeEvents)) {
            $activeEvents[] = $this->message->data->events[0]->eventId;
            SwooleHandler::setValue('activeEventsTable', $activeEventsSwtId, ['events' => json_encode($activeEvents)]);
        }
    }
}
