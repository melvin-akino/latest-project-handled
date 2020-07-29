<?php

namespace App\Handlers;

use Illuminate\Support\Facades\{DB, Log, Redis};
use Exception;
use App\Facades\SwooleHandler;

class OddsTransformationHandler
{
    protected $message;
    protected $offset;
    protected $internalParameters;
    protected $uid       = null;

    public function init($offset, $internalParameters)
    {
        $message                  = SwooleHandler::getValue('oddsKafkaPayloadsTable', $offset);
        $this->message            = json_decode($message['message']);
        $this->internalParameters = $internalParameters;
        $this->offset             = $offset;
        return $this;
    }

    public function handle()
    {
        try {
            $swoole   = app('swoole');

            list(
                'providerId' => $providerId,
                'sportId'    => $sportId,
                'parameters' => $parameters
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
                        date("Ymd", strtotime($this->message->data->referenceSchedule)),
                        $sportId,
                        $masterLeagueId,
                        $this->message->data->events[0]->eventId
                    ]);
                }

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

                            if (array_key_exists('points', $marketSelection)) {
                                $points = $marketSelection->points;
                            }

                            $oddRecord = SwooleHandler::getValue('oddRecordsTable', 'sId:' . $sportId . ':pId:' . $providerId . ':marketId:' . $marketSelection->market_id);

                            $marketPointsRedis       = 'marketPoints:' . $marketSelection->market_id;
                            $marketPointsOffsetRedis = 'offsetMarketPoints:' . $marketSelection->market_id;
                            if (
                                !Redis::exists($marketPointsOffsetRedis) ||
                                (int) Redis::get($marketPointsOffsetRedis) < (int) $this->offset
                            ) {
                                Redis::set($marketPointsOffsetRedis, $this->offset);
                                Redis::set($marketPointsRedis, $points);
                            }

                            if ($oddRecord) {
                                if ($oddRecord['odds'] != $odds) {
                                    $oddsUpdated = [
                                        'market_id'   => $oddRecord['memUID'],
                                        'odds'        => $odds,
                                        'provider_id' => $providerId
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

                        $marketPointsRedis       = 'marketPoints:' . $marketSelection->market_id;
                        $marketPointsOffsetRedis = 'offsetMarketPoints:' . $marketSelection->market_id;
                        if (
                            !Redis::exists($marketPointsOffsetRedis) ||
                            (int) Redis::get($marketPointsOffsetRedis) < (int) $this->offset
                        ) {
                            Redis::set($marketPointsOffsetRedis, $this->offset);
                            Redis::set($marketPointsRedis, $points);
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
                        if (!empty($getEvents['market_odds'])) {
                            if ($swoole->isEstablished($fd['value'])) {
                                $swoole->push($fd['value'], json_encode([
                                    'getAdditionalEvents' => [$getEvents]
                                ]));
                            }
                        } else {
                            SwooleHandler::remove('eventRecordsTable', $eventSwtId);
                            SwooleHandler::remove('mlEventsTable', implode(':', [
                                $sportId,
                                $masterLeagueId,
                                $multiTeam['home']['id'],
                                $multiTeam['away']['id'],
                                date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule))
                            ]));
                        }
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
