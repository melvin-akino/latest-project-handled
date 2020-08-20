<?php

namespace App\Processes;

use RdKafka\TopicConf;
use App\Jobs\{
    TransformKafkaMessageOpenOrders,
    TransformKafkaMessageSettlement,
    TransformKafkaMessageBalance
};
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

                $kafkaConsumer = resolve('LowLevelConsumer');

                $queue = $kafkaConsumer->newQueue();

                $topicConf = new TopicConf();
                $topicConf->set('enable.auto.commit', 'false');
                $topicConf->set('auto.commit.interval.ms', 100);
                $topicConf->set('offset.store.method', 'broker');
                $topicConf->set('auto.offset.reset', 'latest');

                $openOrdersTopic = $kafkaConsumer->newTopic(env('KAFKA_SCRAPE_OPEN_ORDERS', 'OPEN-ORDERS'), $topicConf);
                $openOrdersTopic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);

                $balanceTopic = $kafkaConsumer->newTopic(env('KAFKA_SCRAPE_BALANCE', 'BALANCE'), $topicConf);
                $balanceTopic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);

                $settlementTopic = $kafkaConsumer->newTopic(env('KAFKA_SCRAPE_SETTLEMENTS', 'SCRAPING-SETTLEMENTS'), $topicConf);
                $settlementTopic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);

                while (!self::$quit) {
                    $message = $queue->consume(0);
                    if (!is_null($message)) {
                        if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                            $payload = json_decode($message->payload);

                            switch ($payload->command) {
                                case 'balance':
                                    if (empty($payload->data->provider) || empty($payload->data->username) || empty($payload->data->available_balance) || empty($payload->data->currency)) {
                                        Log::info("Balance Transformation ignored - No Data Found");
                                        break;
                                    }
                                    TransformKafkaMessageBalance::dispatch($payload);
                                    break;
                                case 'orders':
                                    TransformKafkaMessageOpenOrders::dispatch($payload);
                                    break;
                                case 'settlement':
                                    if (empty($payload->data)) {
                                        Log::info("Settlement Transformation ignored - No Data Found");
                                        break;
                                    }

                                    TransformKafkaMessageSettlement::dispatch($payload);
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
