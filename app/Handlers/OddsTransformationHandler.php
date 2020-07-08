<?php

namespace App\Handlers;

use App\Models\{League, Team};
use Illuminate\Support\Facades\{DB, Log};
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
            $eventsTable              = $swoole->eventsTable;
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
                'master_team_away_id' => $multiTeam['away']['id']
                ) = $parameters;


            DB::beginTransaction();

            list($leagueId, $masterLeagueId) = $this->saveLeaguesData($swoole, $providerId, $sportId, $this->message->data->leagueName, $masterLeagueId);
            list($team, $multiTeam) = $this->saveTeamsData($swoole, $providerId, $sportId, $this->message->data->homeTeam, $this->message->data->awayTeam, $multiTeam);

            DB::commit();

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
                    $subTasks['remove-previous-market'] = true;

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
                        $oddTypesData = SwooleHandler::getValue('oddTypesTable', "oddType:" . $columns->oddsType);
                        if ($oddTypesData) {
                            $oddTypeId = $oddTypesData['id'];
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

                        $sportsOddsTypeData = SwooleHandler::getValue('sportOddTypesTable', $sportOddTypeSwtId);
                        if (!$sportsOddsTypeData) {
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
                                    'odd_type_id'             => $oddTypeId,
                                    'provider_id'             => $providerId,
                                    'market_event_identifier' => $event->eventId,
                                    'market_flag'             => strtoupper($markets->indicator),
                                ];

                                continue;
                            }

                            $masterEventMarketSwtId = implode(':', [
                                "pId:" . $providerId,
                                "meUID:" . $uid,
                                "bId:" . $markets->market_id
                            ]);

                            $isMarketSame = true;

                            $eventMarkets = $eventMarketsTable[$masterEventMarketSwtId];

                            if (!empty($eventMarkets)) {
                                $memUID = $eventMarkets['master_event_market_unique_id'];
                                $odds   = $eventMarkets['odds'];

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

    private function saveLeaguesData($swoole, $providerId, $sportId, $leagueName, $masterLeagueId)
    {
        if (empty($masterLeagueId) && $providerId == self::HG) {
            $mId = SwooleHandler::getValueFromKey('masterLeaguesTable', 'name', $leagueName, 'id');
            if (empty($mId)) {
                Log::debug($leagueName);
                $masterLeagueId = DB::table('master_leagues')->insertGetId([
                    'sport_id' => $sportId,
                    'name'     => $leagueName
                ]);
            } else {
                $masterLeagueId = $mId;
            }
        }

        /**
         * Check if league exist in leagues records
         */
        $doesExist = false;
        foreach ($swoole->rawLeaguesTable as $key => $value) {
            if ($sportId == $value['sport_id'] &&
                $providerId == $value['provider_id'] &&
                $leagueName == $value['name']
            ) {
                $leagueId  = $value['id'];
                $doesExist = true;

                League::where('id', $leagueId)->update(['master_league_id' => $masterLeagueId]);
                break;
            }
        }

        if (!$doesExist) {
            $league = League::create([
                'sport_id'         => $sportId,
                'provider_id'      => $providerId,
                'name'             => $leagueName,
                'master_league_id' => $masterLeagueId
            ]);

            $leagueId = $league->id;

            SwooleHandler::setValue('rawLeaguesTable', 'leagueId:' . $league->id, [
                'id'          => $league->id,
                'sport_id'    => $sportId,
                'provider_id' => $providerId,
                'name'        => $league->name
            ]);

        }

        if ($providerId == self::HG) {
            SwooleHandler::setValue('leaguesTable', implode(':', [
                'sId:' . $sportId,
                'pId:' . $providerId,
                'id:' . $masterLeagueId
            ]), [
                'id'                 => $masterLeagueId,
                'provider_id'        => $providerId,
                'sport_id'           => $sportId,
                'master_league_name' => $leagueName,
                'league_name'        => $leagueName,
                'raw_id'             => $leagueId,
            ]);
        }

        return [$leagueId, $masterLeagueId];
    }

    private function saveTeamsData($swoole, $providerId, $sportId, $team1, $team2, $multiTeam)
    {
        $team = ['home' => (object) [], 'away' => (object) []];

        if (empty($multiTeam['home']['id']) && $providerId == self::HG) {
            $tId = SwooleHandler::getValueFromKey('masterTeamsTable', 'name', $team1, 'id');
            if (empty($tId)) {
                $multiTeam['home']['id'] = DB::table('master_teams')->insertGetId([
                    'sport_id' => $sportId,
                    'name'     => $team1
                ]);
            } else {
                $multiTeam['home']['id'] = $tId;
            }
        }

        /**
         * Check if team exist in teams records
         */
        $doesExist = false;
        foreach ($swoole->rawTeamsTable as $key => $value) {
            if ($sportId == $value['sport_id'] &&
                $providerId == $value['provider_id'] &&
                $team1 == $value['name']
            ) {
                $team['home']->id   = $value['id'];
                $team['home']->name = $value['name'];
                $doesExist          = true;

                if (!empty($multiTeam['home']['id']) && empty($value['master_team_id'])) {
                    // Team::where('id', $value['id'])->update(['master_team_id' => $multiTeam['home']['id']]);
                    SwooleHandler::setColumnValue('rawTeamsTable', 'teamId:' . $value['id'], 'master_team_id', $multiTeam['home']['id']);
                }
                break;
            }
        }
        if (!$doesExist) {
            // $team['home'] = Team::create([
            //     'sport_id'       => $sportId,
            //     'name'           => $team1,
            //     'provider_id'    => $providerId,
            //     'master_team_id' => $multiTeam['home']['id']
            // ]);

            if (!empty($multiTeam['home']['id'])) {
                SwooleHandler::setValue('rawTeamsTable', 'teamId:' . $team['home']->id, [
                    'id'          => $team['home']->id,
                    'sport_id'    => $sportId,
                    'provider_id' => $providerId,
                    'name'        => $team['home']->name
                ]);
            }
        }

        if ($providerId == self::HG) {
            SwooleHandler::setValue('teamsTable', implode(':', [
                'pId:' . $providerId,
                'id:' . $multiTeam['home']['id']
            ]), [
                'id'               => $multiTeam['home']['id'],
                'team_name'        => $team['home']->name,
                'master_team_name' => $team['home']->name,
                'provider_id'      => $providerId,
                'raw_id'           => $team['home']->id
            ]);
        }

        if (empty($multiTeam['away']['id']) && $providerId == self::HG) {
            $tId = SwooleHandler::getValueFromKey('masterTeamsTable', 'name', $team1, 'id');
            if (empty($tId)) {
                // $multiTeam['away']['id'] = DB::table('master_teams')->insertGetId([
                //     'sport_id' => $sportId,
                //     'name'     => $team2
                // ]);
                SwooleHandler::setValue('masterTeamsTable', 'id:' . $multiTeam['away']['id'], [
                    'id'       => $multiTeam['away']['id'],
                    'sport_id' => $sportId,
                    'name'     => $team2
                ]);
            } else {
                $multiTeam['away']['id'] = $tId;
            }
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
                $team['away']->id   = $value['id'];
                $team['away']->name = $value['name'];
                $doesExist          = true;

                Team::where('id', $value['id'])->update(['master_team_id' => $multiTeam['away']['id']]);
                break;
            }
        }
        if (!$doesExist) {
            $team['away'] = Team::create([
                'sport_id'       => $sportId,
                'name'           => $team2,
                'provider_id'    => $providerId,
                'master_team_id' => $multiTeam['away']['id']
            ]);

            if (!empty($multiTeam['away']['id']) && empty($value['master_team_id'])) {
                SwooleHandler::setValue('rawTeamsTable', 'teamId:' . $team['away']->id, [
                    'id'          => $team['away']->id,
                    'sport_id'    => $sportId,
                    'provider_id' => $providerId,
                    'name'        => $team['away']->name
                ]);
            }
        }

        if ($providerId == self::HG) {
            SwooleHandler::setValue('teamsTable', implode(':', [
                'pId:' . $providerId,
                'id:' . $multiTeam['away']['id']
            ]), [
                'id'               => $multiTeam['away']['id'],
                'team_name'        => $team['away']->name,
                'master_team_name' => $team['away']->name,
                'provider_id'      => $providerId,
                'raw_id'           => $team['away']->id
            ]);
        }

        return [$team, $multiTeam];
    }
}
