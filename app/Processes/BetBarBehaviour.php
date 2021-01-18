<?php

namespace App\Processes;

use App\Facades\SwooleHandler;
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
                        foreach ($swoole->pendingOrdersWithinExpiryTable as $key => $pendingOrder) {
                            if ($pendingOrder['created_at'] < Carbon::now()->subSeconds($pendingOrder['order_expiry'])) {
                                SwooleHandler::remove('pendingOrdersWithinExpiryTable', $key);
                                SwooleHandler::setValue('topicTable', 'userId:' . $pendingOrder['user_id'] . ':unique:' . uniqid(), [
                                    'user_id'    => $pendingOrder['user_id'],
                                    'topic_name' => 'removal-bet-' . $pendingOrder['id']
                                ]);

                                $toLogs = [
                                    "class"       => "BetBarBehaviour",
                                    "message"     => [
                                        'user_id'    => $pendingOrder['user_id'],
                                        'topic_name' => 'removal-bet-' . $pendingOrder['id']
                                    ],
                                    "module"      => "PROCESS",
                                    "status_code" => 206,
                                ];
                                monitorLog('kafkalog', 'info', $toLogs);
                            }
                        }
                        $initialTime = $newTime;
                    }
                    usleep(1000000);
                }
            }
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "BetBarBehaviour",
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
