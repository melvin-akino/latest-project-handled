<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\DB;
use Swoole\Http\Server;
use Swoole\Process;

class WsSubscriberData implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        while (!self::$quit) {
            self::getAdditionalLeagues($swoole);
            self::getForRemovallLeagues($swoole);
            sleep(1);
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }

    private static function getAdditionalLeagues($swoole)
    {
        $leaguesData = [];
        $table = $swoole->wsTable;
        foreach ($table as $key => $row) {
            if (strpos($key, 'fd:') === 0) {
                $sports = DB::table('sports')->where('is_enabled', true)->get()->toArray();
                foreach ($sports as $sport) {
                    $userAdditionalLeague = $swoole->wsTable->get('userAdditionalLeagues:' . $row['value'] . ':sportId:' . $sport->id);
                    $leagues = $swoole->leaguesTable;
                    foreach ($leagues as $key => $league) {
                        if (strpos($key, $sport->id . ':') === 0) {
                            if ($league['timestamp'] > $userAdditionalLeague['value']) {
                                $leaguesData[] = [
                                    'name'        => $league['multi_league'],
                                    'match_count' => $league['match_count']
                                ];
                            }
                        }
                    }
                }
                $fd = $swoole->wsTable->get('uid:' . $row['value']);
                $swoole->push($fd['value'], json_encode(['getAdditionalLeagues' => $leaguesData]));
            }
        }
    }

    private static function getForRemovallLeagues($swoole)
    {
        $leaguesData = [];
        $table = $swoole->wsTable;
        foreach ($table as $key => $row) {
            if (strpos($key, 'fd:') === 0) {
                $deletedLeagues = $swoole->deletedLeaguesTable;
                foreach ($deletedLeagues as $key => $league) {
                    $leaguesData[] = [
                        'league_id' => $league['value'],
                    ];
                }
                $fd = $swoole->wsTable->get('uid:' . $row['value']);
                $swoole->push($fd['value'], json_encode(['getForRemovalLeagues' => $leaguesData]));
            }
        }
    }
}
