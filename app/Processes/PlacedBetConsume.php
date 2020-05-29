<?php

namespace App\Processes;

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
                $kafkaConsumer = resolve('KafkaConsumer');
                $kafkaConsumer->subscribe([env('KAFKA_BET_PLACED', 'PLACED-BET')]);

                Log::info("Bet Consume Starts");
                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(0);
                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $payload = json_decode($message->payload);

                        if (empty($payload->data->status) || empty($payload->data->odds)) {
                            Log::info("Bet Transformation ignored - Status Or Odds Not Found");
                            $kafkaConsumer->commit($message);
                            Log::channel('kafkalog')->info(json_encode($message));
                            continue;
                        } else if (strpos($payload->data->reason, "Internal Error: Session Inactive") >= 0) {
                            Log::info("Bet Transformation ignored - Internal error");
                            $kafkaConsumer->commit($message);
                            continue;
                        }

                        TransformKafkaMessageBet::dispatch($payload);

                        $kafkaConsumer->commit($message);
                        Log::channel('kafkalog')->info(json_encode($message));
                        continue;
                    }
                    usleep(100000);
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
