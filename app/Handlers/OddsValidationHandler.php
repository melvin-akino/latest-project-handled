<?php

namespace App\Handlers;

use App\Facades\SwooleHandler;
use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Exception;
use Illuminate\Support\Facades\Redis;

class OddsValidationHandler
{
    public $message;

    protected $offset;
    protected $updated = false;
    protected $uid     = null;
    protected $messageObject;

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

    public function init($message, $offset, $swoole)
    {
        $this->message = $message;
        $this->offset  = $offset;
        $this->messageObject  = unserialize(serialize($message));
        $this->swoole = $swoole;
        return $this;
    }

    public function handle()
    {
        try {
            $swoole                             = $this->swoole;
            $subTasks['remove-previous-market'] = [];
            $parameters                         = [];
            $withChange                         = true;

            /** DATABASE TABLES */
            /** LOOK-UP TABLES */
            $providersTable = $swoole->providersTable;
            $sportsTable    = $swoole->sportsTable;
            $leaguesTable   = $swoole->leaguesTable;
            $teamsTable     = $swoole->teamsTable;

            if (!isset($this->message->data->events)) {
                appLog('info', "Transformation ignored - No Event Found");
                return;
            }

            if (env('APP_ENV') != "local") {
                if (!Redis::exists('type:odds:requestUID:' . $this->message->request_uid)) {
                    appLog('info', "Transformation ignored - Request UID is not from ML");
                    return;
                }
            }

            /**
             * Checks if hash is the same as old hash
             */
            $transformedSwtId           = 'eventIdentifier:' . $this->message->data->events[0]->eventId;
            $oddsValidationObject       = $this->messageObject;
            $toHashMessage              = $oddsValidationObject->data;

            $toHashMessage->runningtime = null;
            $toHashMessage->id          = null;

            $payloadHash = SwooleHandler::getValue('transformedTable', $transformedSwtId);
            if ($payloadHash) {
                $ts   = $payloadHash['ts'];
                $hash = $payloadHash['hash'];

                if ($ts > $this->message->request_ts) {
                    appLog('info', "Transformation ignored - Old Timestamp");
                    return;
                }

                if ($hash == md5(json_encode((array) $toHashMessage)) && !empty($oddsPayloadObject->data->events[0]->market_odds[0]->marketSelection)) {
                    appLog('info', "Transformation ignored - No change");
                    $withChange = false;
                }
            } else {
                SwooleHandler::setValue('transformedTable', $transformedSwtId, [
                    'ts'   => $this->message->request_ts,
                    'hash' => md5(json_encode((array) $toHashMessage))
                ]);
            }

            foreach ($this->disregard as $disregard) {
                if (strpos($this->message->data->leagueName, $disregard) !== false) {
                    appLog('info', "Transformation ignored - Filtered League");
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
                appLog('info', "Transformation ignored - Provider doesn't exist");
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
                appLog('info', "Transformation ignored - Sport doesn't exist");
                return;
            }

            $leagueExist = false;
            foreach ($leaguesTable as $k => $v) {
                if ($v['sport_id'] == $sportId && $v['provider_id'] == $providerId && $v['league_name'] == $this->message->data->leagueName) {
                    $parameters['master_league_id']   = $leaguesTable->get($k)['id'];
                    $parameters['league_id']          = $leaguesTable->get($k)['raw_id'];
                    $parameters['master_league_name'] = $leaguesTable->get($k)['master_league_name'];
                    $leagueExist                      = true;
                    break;
                }
            }

            if (!$leagueExist) {
                appLog('info', "Transformation ignored - League is not in the masterlist");
                return;
            }

            $competitors = [
                'home' => $this->message->data->homeTeam,
                'away' => $this->message->data->awayTeam,
            ];
            foreach ($competitors as $key => $row) {
                $teamExist = false;

                foreach ($teamsTable as $k => $v) {
                    if ($v['provider_id'] == $providerId && $v['team_name'] == $row) {
                        $parameters['master_team_' . $key . '_id']   = $v['id'];
                        $parameters['team_' . $key . '_id']          = $v['raw_id'];
                        $parameters['master_team_' . $key . '_name'] = $v['master_team_name'];
                        $teamExist                                   = true;
                        break;
                    }
                }

                if (!$teamExist) {
                    appLog('info', "Transformation ignored - No Available Teams in the masterlist");
                    return;
                }
            }

            SwooleHandler::setValue('oddsKafkaPayloadsTable', $this->offset, ['message' => json_encode($this->message)]);
            $transformKafkaMessageOdds = app('TransformKafkaMessageOdds');
            Log::info("Executing Task for offset:" . $this->offset);
            Task::deliver($transformKafkaMessageOdds->init($this->offset, compact('providerId', 'sportId', 'parameters', 'withChange')));
            Log::info("Transformation - validation completed");

        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getLine());
        }
    }
}
