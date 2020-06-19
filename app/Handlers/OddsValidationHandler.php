<?php

namespace App\Handlers;

use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use App\Tasks\{TransformKafkaMessageOdds, TransformKafkaMessageEventData, UpdateMatchedEventData};
use Exception;

class OddsValidationHandler
{
    protected $message;
    protected $oddsTransformationHandler;
    protected $updated = false;
    protected $uid     = null;

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

    public function init($message, $oddsTransformationHandler)
    {
        $this->message                   = $message;
        $this->oddsTransformationHandler = $oddsTransformationHandler;
        return $this;
    }

    public function handle()
    {
        try {
            $swoole                             = app('swoole');
            $subTasks['remove-previous-market'] = [];

            /** DATABASE TABLES */
            /** LOOK-UP TABLES */
            $providersTable = $swoole->providersTable;
            $sportsTable    = $swoole->sportsTable;
            $leaguesTable   = $swoole->leaguesTable;
            $teamsTable     = $swoole->teamsTable;

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

            foreach ($this->disregard as $disregard) {
                if (strpos($this->message->data->leagueName, $disregard) !== false) {
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

            Task::deliver(new TransformKafkaMessageEventData($this->message, compact('providerId', 'sportId')));

            $leagueExist = false;
            foreach ($leaguesTable as $k => $v) {
                if ($v['sport_id'] == $sportId && $v['provider_id'] == $providerId && $v['league_name'] == $this->message->data->leagueName) {
                    $multiLeagueId    = $leaguesTable->get($k)['id'];
                    $leagueId         = $leaguesTable->get($k)['raw_id'];
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
            foreach ($competitors as $key => $row) {
                $teamExist = false;
                foreach ($teamsTable as $k => $v) {
                    if ($v['provider_id'] == $providerId && $v['team_name'] == $row) {
                        $multiTeam[$key]['id']     = $teamsTable->get($k)['id'];
                        $multiTeam[$key]['name']   = $teamsTable->get($k)['team_name'];
                        $multiTeam[$key]['raw_id'] = $teamsTable->get($k)['raw_id'];

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
                $this->oddsTransformationHandler->init($this->message, compact('providerId', 'sportId', 'multiLeagueId', 'masterLeagueName', 'multiTeam', 'leagueId'))->handle();
            } else {
                Task::deliver(new TransformKafkaMessageOdds($this->message, compact('providerId', 'sportId', 'multiLeagueId', 'masterLeagueName', 'multiTeam', 'leagueId'), $this->oddsTransformationHandler));
            }
            Task::deliver(new UpdateMatchedEventData($this->message));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getLine());
        }
    }
}
