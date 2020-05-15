<?php

namespace App\Handlers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use App\Tasks\TransformKafkaMessageOdds;
use Exception;

class OddsValidationHandler
{
    protected $message;
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

    public function __construct($message)
    {
        $this->message = $message;
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

            if (!isset($this->message->data->events)) {
                Log::info("Transformation ignored - No Event Found");
                return;
            }
            $transformedTable = $swoole->transformedTable;

            $transformedSwtId = 'eventIdentifier:' . $this->message->data->events[0]->eventId;
            if ($transformedTable->exists($transformedSwtId)) {
                $ts   = $transformedTable->get($transformedSwtId)['ts'];
                $hash = $transformedTable->get($transformedSwtId)['hash'];
                if ($ts > $this->message->request_ts) {
                    Log::info("Transformation ignored - Old Timestamp");
                    return;
                }

                $toHashMessage               = $this->message->data;
                $toHashMessage->running_time = null;
                $toHashMessage->id           = null;
                if ($hash == md5(json_encode((array) $toHashMessage))) {
                    Log::info("Transformation ignored - No change");
                    return;
                }
            } else {
                $transformedTable->set($transformedSwtId, [
                    'ts'   => $this->message->request_ts,
                    'hash' => md5(json_encode((array) $this->message->data))
                ]);
            }

            foreach ($this->disregard AS $disregard) {
                if (strpos($this->message->data->leagueName, $disregard) === 0) {
                    Log::info("Transformation ignored - Filtered League");
                    return;
                }
            }

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

            $leagueExist = false;
            foreach ($leaguesTable as $k => $v) {
                if ($v['sport_id'] == $sportId && $v['provider_id'] == $providerId && $v['league_name'] == $this->message->data->leagueName) {
                    $multiLeagueId    = $leaguesTable->get($k)['id'];
                    $masterLeagueName = $leaguesTable->get($k)['master_league_name'];

                    $leagueExist = true;
                    break;
                }
            }

            if (!$leagueExist) {
                Log::info("Transformation ignored - League is not in the masterlist");
                return;
            }

            $multiTeam   = [];
            $competitors = [
                'home' => $this->message->data->homeTeam,
                'away' => $this->message->data->awayTeam,
            ];
            foreach ($competitors AS $key => $row) {
                $teamExist = false;
                foreach ($teamsTable as $k => $v) {
                    if ($v['provider_id'] == $providerId && $v['team_name'] == $row) {
                        $multiTeam[$key]['id']   = $teamsTable->get($k)['id'];
                        $multiTeam[$key]['name'] = $teamsTable->get($k)['team_name'];

                        $teamExist = true;
                        break;
                    }
                }

                if (!$teamExist) {
                    Log::info("Transformation ignored - No Available Teams in the masterlist");
                    return;
                }
            }

            $isLeagueSelected = false;
            foreach ($swoole->userSelectedLeaguesTable as $key => $value) {
                if ($value['league_name'] == $this->message->data->leagueName) {
                    $isLeagueSelected = true;
                    break;
                }
            }

            if ($isLeagueSelected) {
                echo 1;
                $oddsTransformationHandler = new OddsTransformationHandler($this->message, compact('providerId', 'sportId', 'multiLeagueId', 'masterLeagueName', 'multiTeam', 'isLeagueSelected'));
                $oddsTransformationHandler->handle();
            } else {
                echo 2;
                Task::deliver(new TransformKafkaMessageOdds($this->message, compact('providerId', 'sportId', 'multiLeagueId', 'masterLeagueName', 'multiTeam', 'isLeagueSelected')));
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getLine());
        }
    }
}
