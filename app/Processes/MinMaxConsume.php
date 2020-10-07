<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;
use PrometheusMatric;

class MinMaxConsume implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            if ($swoole->data2SwtTable->exist('data2Swt')) {
                Log::info("Min Max Consume Starts");

                $kafkaConsumer = resolve('LowLevelConsumer');

                $queue = $kafkaConsumer->newQueue();

                $topicConf = app('KafkaTopicConf');
                $minMaxTransformationHandler = app('MinMaxTransformationHandler');

                $minmaxTopic = $kafkaConsumer->newTopic(env('KAFKA_SCRAPE_MINMAX_ODDS', 'MINMAX-ODDS'), $topicConf);
                $minmaxTopic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);

                while (!self::$quit) {
                    $message = $queue->consume(0);
                    if (!is_null($message)) {
                        if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                            $swoole->priorityTriggerTable->set('priority', ['value' => 1]);
                            $payload = json_decode($message->payload);

                            if (empty($payload->data)) {
                                Log::info("Min Max Transformation ignored - No Data Found");
                                continue;
                            }

                            $doesMinMaxKeyExist = false;
                            foreach ($swoole->minmaxMarketTable as $key => $minmaxValue) {
                                if ($key == 'minmax-market:' . $payload->data->market_id) {
                                    $doesMinMaxKeyExist = true;
                                    break;
                                }
                            }

                            if (!empty($payload->data->timestamp) && $doesMinMaxKeyExist &&
                                $swoole->minmaxMarketTable->get('minmax-market:' . $payload->data->market_id)['value'] >= $payload->data->timestamp
                            ) {
                                Log::info("Min Max Transformation ignored - Same or Old Timestamp");
                                if (env('CONSUMER_PRODUCER_LOG', false)) {
                                    Log::channel('kafkalog')->info(json_encode($message));
                                }
                                continue;
                            }

                            $swoole->minmaxPayloadTable->set('minmax-payload:' . $payload->data->market_id, [
                                'value' => md5(json_encode([
                                    'odds'    => $payload->data->odds,
                                    'minimum' => $payload->data->minimum,
                                    'maximum' => $payload->data->maximum
                                ]))
                            ]);

                            PrometheusMatric::MakeMatrix('pull_market_id_total', 'Min-max  total number of  market id  received.',$payload->data->market_id);

                            Log::info('Minmax calling Task Worker');
                            $minMaxTransformationHandler->init($payload)->handle();

                            $swoole->priorityTriggerTable->del('priority');

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
            Log::error($e->getMessage());
        }

    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
