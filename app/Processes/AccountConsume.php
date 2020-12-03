<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;

class AccountConsume implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            if ($swoole->data2SwtTable->exist('data2Swt')) {
                Log::info("Account Consume Starts");

                $kafkaConsumer                   = app('LowLevelConsumer');
                $topicConf                       = app('KafkaTopicConf');
                $openOrdersTransformationHandler = app('OpenOrdersTransformationHandler');

                $queue = $kafkaConsumer->newQueue();

                $openOrdersTopic = $kafkaConsumer->newTopic(env('KAFKA_SCRAPE_OPEN_ORDERS', 'OPEN-ORDERS'), $topicConf);
                $openOrdersTopic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);

                while (!self::$quit) {
                    $message = $queue->consume(0);
                    if (!is_null($message)) {
                        if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                            $payload = json_decode($message->payload);

                            switch ($payload->command) {
                                case 'orders':
                                    $openOrdersTransformationHandler->init($payload)->handle();
                                    break;
                                default:
                                    break;
                            }
                            if (env('CONSUMER_PRODUCER_LOG', false)) {
                                Log::channel('kafkalog')->info(json_encode($message));
                            }
                            usleep(10000);
                            continue;
                        }
                        usleep(100000);
                    } else {
                        usleep(10000);
                    }
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
