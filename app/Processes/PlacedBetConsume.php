<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\{Process, Coroutine};
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

                $betTransformationHandler = app('BetTransformationHandler');

                $kafkaConsumer = app('KafkaConsumer');
                $kafkaConsumer->subscribe([
                    env('KAFKA_BET_PLACED', 'PLACED-BET'),
                ]);

                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(0);
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

                        $betTransformationHandler->init($payload, $message->offset)->handle();

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
