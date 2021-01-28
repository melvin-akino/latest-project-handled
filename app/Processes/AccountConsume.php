<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\{Process, Coroutine};
use Exception;
use App\Services\WalletService;
use Carbon\Carbon;

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

                $openOrdersTransformationHandler = app('OpenOrdersTransformationHandler');

                $kafkaConsumer = app('KafkaConsumer');
                $kafkaConsumer->subscribe([
                    env('KAFKA_SCRAPE_OPEN_ORDERS', 'OPEN-ORDERS'),
                ]);

                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(0);

                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $payload = json_decode($message->payload);

                        if (empty($payload->data)) {
                            Log::info("Open Orders ignored - No Data Found");
                            Coroutine::sleep(0.01);
                            $kafkaConsumer->commitAsync($message);
                            continue;
                        }

                        $openOrdersTransformationHandler->init($payload)->handle();
                        
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
