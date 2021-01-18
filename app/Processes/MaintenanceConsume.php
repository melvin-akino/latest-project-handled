<?php

namespace App\Processes;

use RdKafka\TopicConf;
use App\Jobs\MaintenanceHandler;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;

class MaintenanceConsume implements CustomProcessInterface
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
                    "class"       => "MaintenanceConsume",
                    "message"     => "Initiating...",
                    "module"      => "PROCESS",
                    "status_code" => 102,
                ];
                monitorLog('monitor_process', 'info', $toLogs);

                $kafkaConsumer = resolve('LowLevelConsumer');

                $queue = $kafkaConsumer->newQueue();

                $topicConf = app('KafkaTopicConf');
                $maintenanceTransformationHandler = app('MaintenanceTransformationHandler');

                $providerMaintenanceTopic = $kafkaConsumer->newTopic(env('KAFKA_SCRAPE_MAINTENANCE', 'PROVIDER-MAINTENANCE'), $topicConf);
                $providerMaintenanceTopic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);

                while (!self::$quit) {
                    $message = $queue->consume(0);
                    if (!is_null($message)) {
                        if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                            $payload = json_decode($message->payload);

                            if (empty($payload->data)) {
                                $toLogs = [
                                    "class"       => "MaintenanceConsume",
                                    "message"     => "Maintenance Transformation ignored - No Data Found",
                                    "module"      => "PRODUCE_ERROR",
                                    "status_code" => 404,
                                ];
                                monitorLog('monitor_process', 'error', $toLogs);
                            } else {
                                $toLogs = [
                                    "class"       => "MaintenanceConsume",
                                    "message"     => "Maintenance calling Task Worker",
                                    "module"      => "PROCESS",
                                    "status_code" => 102,
                                ];
                                monitorLog('monitor_process', 'info', $toLogs);

                                $maintenanceTransformationHandler->init($payload)->handle();
                            }
                        }

                        usleep(1000000);
                    } else {
                        usleep(10000);
                    }
                }
            }
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "MaintenanceConsume",
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
