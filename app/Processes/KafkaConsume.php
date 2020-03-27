<?php

namespace App\Processes;

use App\Tasks\{TransformKafkaMessageEvents, TransformKafkaMessageLeagues, TransformKafkaMessageOdds, TransformKafkaMessageMinMax};
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
            if ($swoole->wsTable->exist('data2Swt')) {
                $kafkaConsumer = resolve('KafkaConsumer');
                $kafkaConsumer->subscribe([
                    env('KAFKA_SCRAPE_ODDS', 'SCRAPING-ODDS'),
                    env('KAFKA_SCRAPE_LEAGUES', 'SCRAPING-PROVIDER-LEAGUES'),
                    env('KAFKA_SCRAPE_EVENTS', 'SCRAPING-PROVIDER-EVENTS'),
                    env('KAFKA_SCRAPE_MINMAX_ODDS', 'MINMAX-ODDS'),
                    env('KAFKA_BET_PLACED', 'PLACED-BET'),
                    env('KAFKA_SCRAPE_OPEN_ORDERS', 'OPEN-ORDERS')
                ]);

                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(120 * 1000);
                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $payload = json_decode($message->payload);

                        switch ($payload->command) {
                            case 'league':
                                Task::deliver(new TransformKafkaMessageLeagues($payload));
                                break;
                            case 'event':
                                Task::deliver(new TransformKafkaMessageEvents($payload));
                                break;
                            case 'minmax':
                                Task::deliver(new TransformKafkaMessageMinMax($payload));
                                break;
                            case 'bet':
                                Task::deliver(new TransformKafkaMessageBet($payload));
                                break;
                            case 'open orders':
                                Task::deliver(new TransformKafkaMessageOpenOrders($payload));
                                break;
                            default:
                                if (!isset($payload->data->events)) {
                                    Log::info("Transformation ignored - No Event Found");
                                    break;
                                }
                                $transformedTable = $swoole->transformedTable;

                                $transformedSwtId = 'eventIdentifier:' . $payload->data->events[0]->eventId;
                                if ($transformedTable->exists($transformedSwtId)) {
                                    $ts = $transformedTable->get($transformedSwtId)['ts'];
                                    $hash = $transformedTable->get($transformedSwtId)['hash'];
                                    if ($ts > $payload->request_ts) {
                                        Log::info("Transformation ignored - Old Timestamp");
                                        break;
                                    }

                                    $toHashMessage = $payload->data;
                                    $toHashMessage->running_time = null;
                                    $toHashMessage->id = null;
                                    if ($hash == md5(json_encode((array)$toHashMessage))) {
                                        Log::info("Transformation ignored - No change");
                                        break;
                                    }
                                } else {
                                    $transformedTable->set($transformedSwtId, [
                                        'ts'   => $payload->request_ts,
                                        'hash' => md5(json_encode((array) $payload->data))
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