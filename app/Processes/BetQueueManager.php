<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\{DB, Log};
use Swoole\Http\Server;
use Swoole\{Process, Coroutine};
use Exception;

class BetQueueManager implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            DB::beginTransaction();

            /**
             * @TODO do the bet queueing
             */

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();

            $toLogs = [
                "class"       => "BetQueueManager",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "PROCESS",
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
