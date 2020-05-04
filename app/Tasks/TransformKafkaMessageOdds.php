<?php

namespace App\Tasks;

use App\Jobs\TransformKafkaMessageOddsSaveToDb;

use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Str;
use Exception;

class TransformKafkaMessageOdds extends Task
{
    protected $message;
    protected $swoole;
    protected $subTasks = [];
    protected $updated = false;
    protected $uid = null;
    protected $dbOptions = [
        'event-only' => true,
        'is-event-new' => true,
        'is-market-different' => true
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

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle()
    {
        try {
            $swoole  = $this->swoole = app('swoole');
            $wsTable = $this->swoole->wsTable;

            foreach ($this->disregard AS $disregard) {
                if (strpos($this->message->data->leagueName, $disregard) === 0) {
                    Log::info("Transformation ignored - Filtered League");
                    return;
                }
            }

            $toInsert = [];
            $this->subTasks['remove-previous-market'] = [];

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
                Log::info("Transformation ignored - Provider doesn't exist");
                return;
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
            } else {
                Log::info("Transformation ignored - Sport doesn't exist");
                return;
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
                $multiLeagueId = $leaguesTable->get($leagueSwtId)['id'];
                $masterLeagueName = $leaguesTable->get($leagueSwtId)['master_league_name'];
            } else {
                Log::info("Transformation ignored - League is not in the masterlist");
                return;
            }

            $multiTeam = [];
            $competitors = [
                'home' => $this->message->data->homeTeam,
                'away' => $this->message->data->awayTeam,
            ];
            foreach ($competitors AS $key => $row) {
                $teamLookUpId = null;
                foreach ($wsTable as $k => $value) {
                    if (strpos($k, 'teamLookUpId:') === 0) {
                        if ($value['value'] == $row) {
                            $teamLookUpId = substr($k, strlen('teamLookUpId:'));
                            break;
                        }
                    }
                }

                /**
                 * TEAMS (MASTER) Swoole Table
                 *
                 * @ref config.laravels.teams
                 *
                 * @var $teamsTable  swoole_table
                 *      $teamSwtId   swoole_table_key    "pId:<$providerId>:teamLookUpId:<$teamLookUpId>"
                 */
                $teamSwtId = implode(':', [
                    "pId:" . $providerId,
                    "teamLookUpId:" . $teamLookUpId
                ]);

                if ($teamsTable->exists($teamSwtId)) {
                    $multiTeam[$key]['id'] = $teamsTable->get($teamSwtId)['id'];
                    $multiTeam[$key]['name'] = $teamsTable->get($teamSwtId)['team_name'];
                } else {
                    Log::info("Transformation ignored - No Available Teams in the masterlist");
                    return;
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
                    $eventId        = $eventsTable->get($eventSwtId)['id'];
                    $uid            = $eventsTable->get($eventSwtId)['master_event_unique_id'];
                    $masterTeamHome = $eventsTable->get($eventSwtId)['master_home_team_name'];
                    $masterTeamAway = $eventsTable->get($eventSwtId)['master_away_team_name'];

                    if ($this->message->data->schedule == 'early' && $eventsTable->get($eventSwtId)['game_schedule'] == 'today') {
                        Log::info("Transformation ignored - event is already in today");
                        return;
                    }

                    if ($this->message->data->schedule == 'today' && $eventsTable->get($eventSwtId)['game_schedule'] == 'inplay') {
                        Log::info("Transformation ignored - event is already in play");
                        return;
                    }

                    if (($eventsTable->get($eventSwtId)['game_schedule'] != "") && ($eventsTable->get($eventSwtId)['game_schedule'] != $this->message->data->schedule)) {
                        $this->subTasks['remove-previous-market'][] = [
                            'uid'    => $uid,
                            'swtKey' => implode(':', [
                                    "pId:"   . $providerId,
                                    "meUID:" . $uid,
                                ]),
                        ];

                        $wsTable->set('eventScheduleChange:' . $uid, ['value' => json_encode([
                            'uid'           => $uid,
                            'game_schedule' => $this->message->data->schedule,
                            'sport_id'      => $sportId
                        ])]);

                        $eventsTable[$eventSwtId]['game_schedule'] = $this->message->data->schedule;
                    }
                } else {
                    $masterTeamHome = $multiTeam['home']['name'];
                    $masterTeamAway = $multiTeam['away']['name'];

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
                'league_name'      => $this->message->data->leagueName,
                'home_team_name'   => $this->message->data->homeTeam,
                'away_team_name'   => $this->message->data->awayTeam,
                'ref_schedule'     => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                'game_schedule'    => $this->message->data->schedule,
                'deleted_at'       => null
            ];

            $this->subTasks['event-raw'] = $toInsert;

            if (!empty($uid)) {
                $this->dbOptions['event-only'] = false;
                if (!empty($eventId)) {
                    $this->dbOptions['is-event-new'] = false;
                }

                $this->uid = $uid;
                $arrayEvents = $this->message->data->events;
                $counter = 0;

                $toInsert['MasterEvent']['swtKey'] = $eventSwtId;
                $toInsert['MasterEvent']['data'] = [
                    'sport_id'               => $sportId,
                    'master_event_unique_id' => $uid,
                    'master_league_name'     => $masterLeagueName,
                    'master_home_team_name'  => $masterTeamHome,
                    'master_away_team_name'  => $masterTeamAway,
                    'ref_schedule'           => date("Y-m-d H:i:s", strtotime($this->message->data->referenceSchedule)),
                    'game_schedule'          => $this->message->data->schedule,
                    'score'                  => $this->message->data->home_score . " - " . $this->message->data->away_score,
                    'running_time'           => $this->message->data->runningtime,
                    'home_penalty'           => $this->message->data->home_redcard,
                    'away_penalty'           => $this->message->data->away_redcard,
                    'deleted_at'             => null
                ];
                $this->subTasks['event'] = $toInsert;

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
                                        $marketOdds = $marketOdds[1];
                                    } else {
                                        $marketOdds = $marketOdds[0];
                                    }
                                }

                                $marketOdds = trim($marketOdds) == '' ? 0 : (float) $marketOdds;

                                if (array_key_exists('points', $markets)) {
                                    $marketPoints = $markets->points;
                                }

                                if ($markets->market_id == "") {
                                    $marketOdds  = 0;

                                    $this->subTasks['remove-event-market'][] = [
                                        'odd_type_id'                   => $oddTypeId,
                                        'provider_id'                   => $providerId,
                                        'event_identifier'              => $event->eventId,
                                        'market_flag'                   => strtoupper($markets->indicator),
                                    ];

                                    continue;
                                }

                                $masterEventMarketSwtId = implode(':', [
                                    "pId:" . $providerId,
                                    "meUID:" . $uid,
                                    "bId:" . $markets->market_id
                                ]);

                                if ($eventMarketsTable->exist($masterEventMarketSwtId) && !empty($markets->market_id)) {
                                    $memUID = $eventMarketsTable->get($masterEventMarketSwtId)['master_event_market_unique_id'];
                                    $odds = $eventMarketsTable->get($masterEventMarketSwtId)['odds'];

                                    if ($odds != $marketOdds) {
                                        $eventMarketsTable[$masterEventMarketSwtId]['odds'] = $marketOdds;
                                        $this->updated = true;
                                        $oddsUpdated = ['market_id' => $memUID, 'odds' => $marketOdds, 'provider_id' => $providerId, 'event_identifier' => $event->eventId ];
                                        if (!empty($marketPoints)) {
                                            $oddsUpdated['points'] = $marketPoints;
                                        }
                                        $updatedOdds[] = $oddsUpdated;
                                    } else {
                                        $this->dbOptions['is-market-different'] = false;
                                    }
                                } else {
                                    $memUID = uniqid();
                                }

                                /** TO INSERT */
                                $toInsert['MasterEventMarket']['swtKey'] = $masterEventMarketSwtId;
                                $toInsert['MasterEventMarket']['data'] = [
                                    'master_event_unique_id'        => $uid,
                                    'odd_type_id'                   => $oddTypeId,
                                    'master_event_market_unique_id' => $memUID,
                                    'is_main'                       => $event->market_type == 1 ? true : false,
                                    'market_flag'                   => strtoupper($markets->indicator),
                                ];

                                $toInsert['EventMarket']['data'] = [
                                    'provider_id'            => $providerId,
                                    'master_event_unique_id' => $uid,
                                    'odd_type_id'            => $oddTypeId,
                                    'odds'                   => $marketOdds,
                                    'odd_label'              => $marketPoints,
                                    'bet_identifier'         => $markets->market_id,
                                    'is_main'                => $event->market_type == 1 ? true : false,
                                    'market_flag'            => strtoupper($markets->indicator),
                                    'event_identifier'       => $event->eventId,
                                    'deleted_at'             => null,
                                ];

                                if ($this->dbOptions['is-market-different']) {
                                    $toInsert['MasterEventMarketLog']['data'] = [
                                        'provider_id' => $providerId,
                                        'odd_type_id' => $oddTypeId,
                                        'odds'        => $marketOdds,
                                        'odd_label'   => $marketPoints,
                                        'is_main'     => $event->market_type == 1 ? true : false,
                                        'market_flag' => strtoupper($markets->indicator),
                                    ];
                                }

                                $this->subTasks['event-market'][] = $toInsert;
                            }
                        }
                    }
                    $counter++;
                }
            }

            $this->subTasks['updated-odds'] = [];
            if ($this->updated) {
                $this->subTasks['updated-odds'] = $updatedOdds;
            }

            if (!empty($this->subTasks['event'])) {
                TransformKafkaMessageOddsSaveToDb::dispatch($this->subTasks, $this->uid, $this->dbOptions);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
