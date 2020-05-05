<?php

namespace App\Processes;

use App\Jobs\{
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

class KafkaConsume implements CustomProcessInterface
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
                    env('KAFKA_SCRAPE_ODDS', 'SCRAPING-ODDS'),
                    env('KAFKA_SCRAPE_LEAGUES', 'SCRAPING-PROVIDER-LEAGUES'),
                    env('KAFKA_SCRAPE_EVENTS', 'SCRAPING-PROVIDER-EVENTS'),
                    env('KAFKA_BET_PLACED', 'PLACED-BET'),
                    env('KAFKA_SCRAPE_OPEN_ORDERS', 'OPEN-ORDERS'),
                    env('KAFKA_SCRAPE_BALANCE', 'BALANCE'),
                    env('KAFKA_SCRAPE_SETTLEMENTS', 'SCRAPING-SETTLEMENTS'),
                ]);

                echo '.';
                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(0);
                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $payload = json_decode($message->payload);

                        switch ($payload->command) {
                            case 'league':
                                Task::deliver(new TransformKafkaMessageLeagues($payload));
                                break;
                            case 'event':
                                Task::deliver(new TransformKafkaMessageEvents($payload));
                                break;
                            case 'bet':
                                if (empty($payload->data->status) || empty($payload->data->odds)) {
                                    Log::info("Bet Transformation ignored - Status Or Odds Not Found");
                                    break;
                                }

                                Task::deliver(new TransformKafkaMessageBet($payload));
                                break;
                            case 'balance':
                                if (empty($payload->data->provider) || empty($payload->data->username) || empty($payload->data->available_balance) || empty($payload->data->currency)) {
                                    Log::info("Balance Transformation ignored - No Data Found");
                                    break;
                                }
                                Task::deliver(new TransformKafkaMessageBalance($payload));
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
                                if (!isset($payload->data->events)) {
                                    Log::info("Transformation ignored - No Event Found");
                                    break;
                                }
                                $transformedTable = $swoole->transformedTable;

                                $transformedSwtId = 'eventIdentifier:' . $payload->data->events[0]->eventId;
                                if ($transformedTable->exists($transformedSwtId)) {
                                    $ts   = $transformedTable->get($transformedSwtId)['ts'];
                                    $hash = $transformedTable->get($transformedSwtId)['hash'];
                                    if ($ts > $payload->request_ts) {
                                        Log::info("Transformation ignored - Old Timestamp");
                                        break;
                                    }

                                    $toHashMessage               = $payload->data;
                                    $toHashMessage->running_time = null;
                                    $toHashMessage->id           = null;
                                    if ($hash == md5(json_encode((array)$toHashMessage))) {
                                        Log::info("Transformation ignored - No change");
                                        break;
                                    }
                                } else {
                                    $transformedTable->set($transformedSwtId, [
                                        'ts'   => $payload->request_ts,
                                        'hash' => md5(json_encode((array)$payload->data))
                                    ]);
                                }
                                Task::deliver(new TransformKafkaMessageOdds($payload));
                                break;
                        }
                        $kafkaConsumer->commitAsync($message);
                        Log::channel('kafkalog')->info(json_encode($message));
                    } else {
                        Log::error(json_encode([$message]));
                    }
                    usleep(10000);
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
