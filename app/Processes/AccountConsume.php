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
                $toLogs = [
                    "class"       => "AccountConsume",
                    "message"     => "Initiating...",
                    "module"      => "PROCESS",
                    "status_code" => 102,
                ];
                monitorLog('monitor_process', 'info', $toLogs);

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
                            $toLogs = [
                                "class"       => "AccountConsume",
                                "message"     => "Open Orders ignored - No Data Found",
                                "module"      => "PROCESS_ERROR",
                                "status_code" => 404,
                            ];
                            monitorLog('kafkalog', 'error', $toLogs);

                            Coroutine::sleep(0.01);

                            $kafkaConsumer->commitAsync($message);
                            continue;
                        }

                        switch ($payload->command) {
                            case 'orders':
                                $openOrdersTransformationHandler->init($payload)->handle();
                                break;
                            default:
                                break;
                        }

                        if (env('CONSUMER_PRODUCER_LOG', false)) {
                            $toLogs = [
                                "class"       => "AccountConsume",
                                "message"     => $message,
                                "module"      => "PROCESS",
                                "status_code" => 206,
                            ];
                            monitorLog('kafkalog', 'info', $toLogs);
                        }

                        Coroutine::sleep(0.01);
                        $kafkaConsumer->commitAsync($message);
                        continue;
                    }
                    Coroutine::sleep(0.01);
                }
            }
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "AccountConsume",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "PRODUCE_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_process', 'error', $toLogs);
        }

    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
