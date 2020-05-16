<?php

namespace App\Processes;

use App\Jobs\{
    TransformKafkaMessageEvents,
    TransformKafkaMessageLeagues
};
use App\Handlers\OddsValidationHandler;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;

class GameConsume implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        $message = null;
        try {
            if ($swoole->data2SwtTable->exist('data2Swt')) {
                $kafkaConsumer = resolve('KafkaLatestConsumer');
                $kafkaConsumer->subscribe([
                    env('KAFKA_SCRAPE_ODDS', 'SCRAPING-ODDS'),
                    env('KAFKA_SCRAPE_LEAGUES', 'SCRAPING-PROVIDER-LEAGUES'),
                    env('KAFKA_SCRAPE_EVENTS', 'SCRAPING-PROVIDER-EVENTS')
                ]);

                Log::info("Game Consume Starts");
                while (!self::$quit) {
                    if ($swoole->priorityTriggerTable->exist('priority')) {
                        usleep(100000);
                        continue;
                    }

                    $message = $kafkaConsumer->consume(0);
                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $payload = json_decode($message->payload);

                        switch ($payload->command) {
                            case 'league':
                                TransformKafkaMessageLeagues::dispatch($payload);
                                break;
                            case 'event':
                                TransformKafkaMessageEvents::dispatch($payload);
                                break;
                            case 'odd':
                                $oddsValidationHandler = new OddsValidationHandler($payload);
                                $oddsValidationHandler->handle();
                                break;
                            default:
                                break;
                        }
                        $kafkaConsumer->commitAsync($message);
                        Log::channel('kafkalog')->info(json_encode($message));
                        usleep(10000);
                        continue;
                    }
                    usleep(100000);
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::debug('Payload' . $message->payload);
        }

    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
