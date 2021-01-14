<?php

namespace App\Handlers;

use App\Facades\SwooleHandler;
use App\Jobs\WsSelectedLeagues;
use App\Models\{MasterLeague, SystemConfiguration, UserSelectedLeague};
use Exception;
use Illuminate\Support\Facades\{Log, DB, Redis};

class LeaguesTransformationHandler
{
    protected $message;
    protected $offset;
    protected $swoole;

    public function init($message, $offset, $swoole)
    {
        $this->message = $message;
        $this->offset  = $offset;
        $this->swoole  = $swoole;

        return $this;
    }

    public function handle()
    {
        try {
            $startTime = microtime(TRUE);

            $swoole = $this->swoole;

            if (env('APP_ENV') != "local") {
                if (!Redis::exists('type:events:requestUID:' . $this->message->request_uid)) {
                    $toLogs = [
                        "class"       => "LeaguesTransformationHandler",
                        "message"     => "Leagues Transformation ignored - Request UID is not from ML",
                        "module"      => "HANDLER_ERROR",
                        "status_code" => 400,
                    ];
                    monitorLog('monitor_handlers', 'error', $toLogs);

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
            if (!SwooleHandler::exists('providersTable', $providerSwtId)) {
                $toLogs = [
                    "class"       => "LeaguesTransformationHandler",
                    "message"     => "Leagues Transformation ignored - Provider doesn't exist",
                    "module"      => "HANDLER_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_handlers', 'error', $toLogs);

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
            if (!SwooleHandler::exists('sportsTable', $sportSwtId)) {
                $toLogs = [
                    "class"       => "LeaguesTransformationHandler",
                    "message"     => "Leagues Transformation ignored - Sport doesn't exist",
                    "module"      => "HANDLER_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_handlers', 'error', $toLogs);

                return;
            } else {
                $sports = SwooleHandler::getValue('sportsTable', $sportSwtId);
                $sportId = $sports['id'];
            }

            $leagues = (array) $this->message->data->leagues;
            $unusedMasterLeagues = MasterLeague::whereNotIn('name', $leagues)->pluck('name')->toArray();
            UserSelectedLeague::removeByMasterLeagueNamesAndSchedule($unusedMasterLeagues, $this->message->data->schedule);
            foreach (SwooleHandler::table('userSelectedLeaguesTable') as $key => $userSelectedLeague) {
                if (in_array($userSelectedLeague['league_name'], $unusedMasterLeagues) &&
                    $userSelectedLeague['schedule'] == $this->message->data->schedule
                ) {
                    SwooleHandler::remove('userSelectedLeaguesTable', $key);
                }
            }

            if (!SwooleHandler::exists('updateLeaguesTable', 'leagueCount:' . $this->message->data->schedule) ||
                SwooleHandler::getValue('updateLeaguesTable', 'leagueCount:' . $this->message->data->schedule)['value'] != count($leagues)
            ) {
                SwooleHandler::setValue('updateLeaguesTable', 'leagueCount:' . $this->message->data->schedule, ['value' => count($leagues)]);
                SwooleHandler::setValue('updateLeaguesTable', 'updateLeagues', ['value' => 1]);
            }


//            foreach (SwooleHandler::table('wsTable') as $key => $row) {
//                if (strpos($key, 'uid:') === 0 && $swoole->isEstablished($row['value'])) {
//                    $userId = substr($key, strlen('uid:'));
//                    WsSelectedLeagues::dispatch($userId, [1 => $sportId]);
//                }
//            }

            $endTime         = microtime(TRUE);
            $timeConsumption = $endTime - $startTime;

            $toLogs = [
                "class"       => "LeaguesTransformationHandler",
                "message"     => [
                    'request_uid'      => json_encode($this->message->request_uid),
                    'request_ts'       => json_encode($this->message->request_ts),
                    'offset'           => json_encode($this->offset),
                    'time_consumption' => json_encode($timeConsumption),
                    'leagues'          => json_encode($this->message->data->leagues),
                ],
                "module"      => "HANDLER",
                "status_code" => 200,
            ];
            monitorLog('monitor_handlers', 'info', $toLogs);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "LeaguesTransformationHandler",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "HANDLER_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_handlers', 'error', $toLogs);
        }
    }
}
