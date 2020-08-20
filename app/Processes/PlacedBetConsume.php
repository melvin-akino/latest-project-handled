<?php

namespace App\Processes;

use RdKafka\TopicConf;
use App\Jobs\TransformKafkaMessageBet;
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
                Log::info("Bet Consume Starts");

                $kafkaConsumer = resolve('LowLevelConsumer');

                $queue = $kafkaConsumer->newQueue();

                $topicConf = new TopicConf();
                $topicConf->set('enable.auto.commit', 'false');
                $topicConf->set('auto.commit.interval.ms', 100);
                $topicConf->set('offset.store.method', 'broker');
                $topicConf->set('auto.offset.reset', 'latest');

                $placedBetTopic = $kafkaConsumer->newTopic(env('KAFKA_BET_PLACED', 'PLACED-BET'), $topicConf);
                $placedBetTopic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);

                while (!self::$quit) {
                    $message = $queue->consume(0);
                    if (!is_null($message)) {
                        if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                            $payload = json_decode($message->payload);

                            if (empty($payload->data->status) || empty($payload->data->odds)) {
                                Log::info("Bet Transformation ignored - Status Or Odds Not Found");
                                if (env('CONSUMER_PRODUCER_LOG', false)) {
                                    Log::channel('kafkalog')->info(json_encode($message));
                                }
                                continue;
                            } else if (strpos($payload->data->reason, "Internal Error: Session Inactive")) {
                                Log::info("Bet Transformation ignored - Internal error");
                                continue;
                            }

                            TransformKafkaMessageBet::dispatch($payload);

                            if (env('CONSUMER_PRODUCER_LOG', false)) {
                                Log::channel('kafkalog')->info(json_encode($message));
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
            Log::error(json_encode([
                'PlacedBetConsume' => [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                ]
            ]));
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
