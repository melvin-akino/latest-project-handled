<?php

namespace App\Processes;

use App\Facades\SwooleHandler;
use App\Jobs\WSForBetBarRemoval;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;
use Carbon\Carbon;

class BetBarBehaviour implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            if ($swoole->data2SwtTable->exist('data2Swt')) {
                $initialTime = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));

                while (!self::$quit) {
                    $newTime = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                    if ($newTime->diffInSeconds(Carbon::parse($initialTime)) >= 30) {
                        foreach ($swoole->pendingOrdersWithin30Table as $key => $pendingOrder) {
                            $fd = $swoole->wsTable->get('uid:' . $pendingOrder['user_id']);
                            if ($pendingOrder['created_at'] < Carbon::now()->subSeconds(30)) {
                                SwooleHandler::remove('pendingOrdersWithin30Table', $key);
                                WSForBetBarRemoval::dispatch($fd['value'], $pendingOrder['id']);
                            }
                        }
                        $initialTime = $newTime;
                    }
                    usleep(1000000);
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
