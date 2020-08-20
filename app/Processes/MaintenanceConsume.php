<?php

namespace App\Processes;

use RdKafka\TopicConf;
use App\Jobs\TransformKafkaMessageMaintenance;
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
                Log::info("Maintenance Consume Starts");

                $kafkaConsumer = resolve('LowLevelConsumer');

                $queue = $kafkaConsumer->newQueue();

                $topicConf = new TopicConf();
                $topicConf->set('enable.auto.commit', 'false');
                $topicConf->set('auto.commit.interval.ms', 100);
                $topicConf->set('offset.store.method', 'broker');
                $topicConf->set('auto.offset.reset', 'latest');

                $providerMaintenanceTopic = $kafkaConsumer->newTopic(env('KAFKA_SCRAPE_MAINTENANCE', 'PROVIDER-MAINTENANCE'), $topicConf);
                $providerMaintenanceTopic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);

                while (!self::$quit) {
                    $message = $queue->consume(0);
                    if (!is_null($message)) {
                        if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                            $payload = json_decode($message->payload);

                            if (empty($payload->data)) {
                                Log::info("Maintenance Transformation ignored - No Data Found");
                            } else {
                                Log::info('Maintenance calling Task Worker');
                                TransformKafkaMessageMaintenance::dispatchNow($payload);
                            }
                        }

                        usleep(1000000);
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
