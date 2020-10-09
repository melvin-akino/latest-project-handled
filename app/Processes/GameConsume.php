<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;

class GameConsume implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        $message = null;
        try {
            if ($swoole->data2SwtTable->exist('data2Swt')) {
                Log::info("Game Consume Starts");

                $kafkaConsumer = resolve('LowLevelConsumer');

                $queue = $kafkaConsumer->newQueue();

                $topicConf = app('KafkaTopicConf');

                $oddsTopic = $kafkaConsumer->newTopic(env('KAFKA_SCRAPE_ODDS', 'SCRAPING-ODDS'), $topicConf);
                $oddsTopic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);

                $eventsTopic = $kafkaConsumer->newTopic(env('KAFKA_SCRAPE_EVENTS', 'SCRAPING-PROVIDER-EVENTS'), $topicConf);
                $eventsTopic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);

                $oddsValidationHandler       = app('OddsValidationHandler');
                $eventsTransformationHandler = app('EventsTransformationHandler');

                while (!self::$quit) {
                    $message = $queue->consume(0);
                    if (!is_null($message)) {
                        if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                            $payload = json_decode($message->payload);

                            if (!isset($payload->command)) {
                                Log::info('Error in GAME CONSUME payload');
                                Log::info($message->payload);
                                continue;
                            }
                            switch ($payload->command) {
                                case 'event':
                                    $eventsTransformationHandler->init($payload, $message->offset, $swoole)->handle();
                                    break;
                                case 'odd':
                                    $oddsValidationHandler->init($payload, $message->offset, $swoole)->handle();
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
