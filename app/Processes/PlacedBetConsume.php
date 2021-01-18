<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
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

                $kafkaConsumer = resolve('LowLevelConsumer');

                $queue = $kafkaConsumer->newQueue();

                $topicConf = app('KafkaTopicConf');
                $betTransformationHandler = app('BetTransformationHandler');

                $placedBetTopic = $kafkaConsumer->newTopic(env('KAFKA_BET_PLACED', 'PLACED-BET'), $topicConf);
                $placedBetTopic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);

                while (!self::$quit) {
                    $message = $queue->consume(0);
                    if (!is_null($message)) {
                        if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                            $payload = json_decode($message->payload);

                            if (empty($payload->data->status) || empty($payload->data->odds)) {
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
                        }
                        usleep(100000);
                    } else {
                        usleep(10000);
                    }
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
