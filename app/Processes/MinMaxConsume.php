<?php

namespace App\Processes;

use App\Jobs\{
    TransformKafkaMessageMinMax,
    TransformKafkaMessageOpenOrders,
    TransformKafkaMessageSettlement
};

use App\Tasks\{
    TransformKafkaMessageEvents,
    TransformKafkaMessageLeagues,
    TransformKafkaMessageOdds,
    TransformKafkaMessageBalance,
    TransformKafkaMessageBet
};

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;
use Storage;

class MinMaxConsume implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            if ($swoole->wsTable->exist('data2Swt')) {
                $kafkaConsumer = resolve('KafkaConsumer');
                $kafkaConsumer->subscribe([
                    env('KAFKA_SCRAPE_MINMAX_ODDS', 'MINMAX-ODDS')
                ]);

                echo '.';
                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(0);
                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $payload = json_decode($message->payload);

                        if (empty($payload->data)) {
                            Log::info("Min Max Transformation ignored - No Data Found");
                            continue;
                        }

                        if (!empty($payload->data->timestamp) &&
                            $swoole->wsTable->exist('minmax-market:' . $payload->data->market_id) &&
                            $swoole->wsTable->get('minmax-market:' . $payload->data->market_id)['value'] >= $payload->data->timestamp
                        ) {
                            Log::info("Min Max Transformation ignored - Same or Old Timestamp");
                            continue;
                        }

                        $swoole->wsTable->set('minmax-payload:' . $payload->data->market_id, [
                            'value' => md5(json_encode([
                                'odds'    => $payload->data->odds,
                                'minimum' => $payload->data->minimum,
                                'maximum' => $payload->data->maximum
                            ]))
                        ]);

                        Log::info('Minmax calling Task Worker');
                        TransformKafkaMessageMinMax::dispatch($payload);

                        $kafkaConsumer->commitAsync($message);
                        Log::channel('kafkalog')->info(json_encode($message));
                    } else {
                        Log::error(json_encode([$message]));
                    }
                    usleep(1000);
                }
            }
        } catch(Exception $e) {
            Log::error($e->getMessage());
        }

    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
