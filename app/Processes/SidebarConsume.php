<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\{Process, Coroutine};
use Exception;
use App\Facades\SwooleHandler;
use App\Models\UserWatchlist;

class SidebarConsume implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            if ($swoole->data2SwtTable->exist('data2Swt')) {
                $toLogs = [
                    "class"       => "SidebarConsume",
                    "message"     => "Initiating...",
                    "module"      => "PROCESS",
                    "status_code" => 102,
                ];
                monitorLog('monitor_process', 'info', $toLogs);

                $kafkaConsumer = app('KafkaConsumer');
                $kafkaConsumer->subscribe([
                    env('KAFKA_SIDEBAR_LEAGUES', 'SIDEBAR-LEAGUES'),
                ]);

                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(0);

                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $payload = json_decode($message->payload, true);

                        $gameSchedule = null;
                        $sidebarLeagues = null;
                        foreach ($payload['data']['sidebar'] as $schedule => $leagues) {
                            $gameSchedule   = $schedule;
                            $sidebarLeagues = $leagues;
                        }

                        if (is_null($gameSchedule) || is_null($sidebarLeagues)) {
                            Log::info("Invalid payload");
                            continue;
                        }

                        $wsTable = SwooleHandler::table('wsTable');
                        $watchlist = UserWatchlist::getAllLeagueCountByUser();
                        foreach ($wsTable as $key => $row) {
                            if (strpos($key, 'uid:') === 0 && $swoole->isEstablished($row['value'])) {
                                $userId = substr($key, strlen('uid:'));
                                $userSidebar = $sidebarLeagues;
                                foreach ($watchlist as $wl) {
                                    if ($userId == $wl->user_id && $gameSchedule == $wl->game_schedule) {
                                        foreach ($userSidebar as $k => $usl) {
                                            if ($usl['master_league_id'] == $wl->master_league_id) {
                                                $userSidebar[$k]['match_count'] = (int) $userSidebar[$k]['match_count'] - $wl->match_count;
                                            } 

                                            if ($userSidebar[$k]['match_count'] <= 0) {
                                                unset($userSidebar[$k]);
                                            }
                                        }
                                    };
                                }

                                $userSport = getUserDefault($userId, 'sport');
                                if ($userSport['default_sport'] == $payload['data']['sport_id']) {
                                    $swoole->push($row['value'], json_encode(['getSidebarLeagues' => [
                                        $gameSchedule => array_values($userSidebar)
                                    ]]));
                                }
                            }
                        }

                        $kafkaConsumer->commitAsync($message);
                    }

                    Coroutine::sleep(0.01);
                }
            }
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "SidebarConsume",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "PRODUCE_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_process', 'error', $toLogs);
        }

    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
