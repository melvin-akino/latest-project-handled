<?php

namespace App\Processes;

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
                $kafkaConsumer = resolve('KafkaConsumer');
                $kafkaConsumer->subscribe([
                    env('KAFKA_SCRAPE_OPEN_ORDERS', 'OPEN-ORDERS'),
                    env('KAFKA_SCRAPE_BALANCE', 'BALANCE'),
                    env('KAFKA_SCRAPE_SETTLEMENTS', 'SCRAPING-SETTLEMENTS'),
                ]);

                Log::info("Account Consume Starts");
                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(0);
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
                                if (empty($payload->data)) {
                                    Log::info("Open Order Transformation ignored - No Data Found");
                                    break;
                                }

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
                        $kafkaConsumer->commitAsync($message);
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
