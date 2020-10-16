<?php

namespace App\Handlers;

use App\Facades\SwooleHandler;
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

            if (env('APP_ENV') != "local") {
                if (!Redis::exists('type:events:requestUID:' . $this->message->request_uid)) {
                    appLog('info', "Leagues Transformation ignored - Request UID is not from ML");
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
                Log::info("Leagues Transformation ignored - Provider doesn't exist");
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
                Log::info("Leagues Transformation ignored - Sport doesn't exist");
                return;
            }

            $unusedMasterLeagues = MasterLeague::whereNotIn('name', (array) $this->message->data->leagues)->pluck('name')->toArray();
            UserSelectedLeague::removeByMasterLeagueNamesAndSchedule($unusedMasterLeagues, $this->message->data->schedule);
            foreach (SwooleHandler::table('userSelectedLeaguesTable') as $key => $userSelectedLeague) {
                if (in_array($userSelectedLeague['league_name'], $unusedMasterLeagues) &&
                    $userSelectedLeague['schedule'] == $this->message->data->schedule
                ) {
                    SwooleHandler::remove('userSelectedLeaguesTable', $key);
                }
            }

            $endTime         = microtime(TRUE);
            $timeConsumption = $endTime - $startTime;

            Log::channel('scraping-leagues')->info([
                'request_uid'      => json_encode($this->message->request_uid),
                'request_ts'       => json_encode($this->message->request_ts),
                'offset'           => json_encode($this->offset),
                'time_consumption' => json_encode($timeConsumption),
                'events'           => json_encode($this->message->data->leagues),
            ]);
        } catch (Exception $e) {
            Log::error(json_encode(
                [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file'    => $e->getFile(),
                ]
            ));
        }
    }
}
