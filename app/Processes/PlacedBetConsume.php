<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\{Process, Coroutine};
use Exception;

class PlacedBetConsume implements CustomProcessInterface
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
                    "class"       => "PlacedBetConsume",
                    "message"     => "Initiating...",
                    "module"      => "PROCESS",
                    "status_code" => 102,
                ];
                monitorLog('monitor_process', 'info', $toLogs);

                $betTransformationHandler = app('BetTransformationHandler');

                $kafkaConsumer = app('KafkaConsumer');
                $kafkaConsumer->subscribe([
                    env('KAFKA_BET_PLACED', 'PLACED-BET'),
                ]);

                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(0);
                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $payload = json_decode($message->payload);

                        if (empty($payload->data->status)) {
                            $toLogs = [
                                "class"       => "PlacedBetConsume",
                                "message"     => "Bet Transformation ignored - Status Or Odds Not Found",
                                "module"      => "PRODUCE_ERROR",
                                "status_code" => 404,
                            ];
                            monitorLog('monitor_process', 'error', $toLogs);

                            if (env('CONSUMER_PRODUCER_LOG', false)) {
                                $toLogs = [
                                    "class"       => "PlacedBetConsume",
                                    "message"     => $message,
                                    "module"      => "PRODUCE_ERROR",
                                    "status_code" => 206,
                                ];
                                monitorLog('kafkalog', 'info', $toLogs);
                            }
                            continue;
                        } else if (strpos($payload->data->reason, "Internal Error: Session Inactive")) {
                            $toLogs = [
                                "class"       => "PlacedBetConsume",
                                "message"     => "Bet Transformation ignored - Internal error",
                                "module"      => "PRODUCE_ERROR",
                                "status_code" => 400,
                            ];
                            monitorLog('monitor_process', 'error', $toLogs);

                            continue;
                        }

                        $betTransformationHandler->init($payload, $message->offset)->handle();

                        Coroutine::sleep(0.01);
                        $kafkaConsumer->commitAsync($message);

                        continue;
                    }

                    Coroutine::sleep(0.01);
                }
            }
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "PlacedBetConsume",
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
