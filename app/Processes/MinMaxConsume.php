<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\{Process, Coroutine};
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

                $minMaxTransformationHandler = app('MinMaxTransformationHandler');

                $kafkaConsumer = app('KafkaConsumer');
                $kafkaConsumer->subscribe([
                    env('KAFKA_SCRAPE_MINMAX_ODDS', 'MINMAX-ODDS'),
                ]);

                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(0);
                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
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

                        if (env('CONSUMER_PRODUCER_LOG', false)) {
                            Log::channel('kafkalog')->info(json_encode($message));
                        }
                        Coroutine::sleep(0.01);
                        $kafkaConsumer->commitAsync($message);
                        continue;
                    }
                    Coroutine::sleep(0.01);
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
