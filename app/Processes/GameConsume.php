<?php

namespace App\Processes;

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
                Log::info("Game Consume Starts");

                $kafkaConsumer = app('KafkaConsumer');
                $kafkaConsumer->subscribe([
                    env('KAFKA_SCRAPE_ODDS', 'SCRAPING-ODDS'),
                    env('KAFKA_SCRAPE_EVENTS', 'SCRAPING-PROVIDER-EVENTS'),
                    env('KAFKA_SCRAPE_LEAGUES', 'SCRAPING-PROVIDER-LEAGUES')
                ]);

                $oddsValidationHandler       = app('OddsValidationHandler');
                $eventsTransformationHandler = app('EventsTransformationHandler');
                $leaguesTransformationHandler = app('LeaguesTransformationHandler');

                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(0);
                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $payload = json_decode($message->payload);
                        if (!isset($payload->command)) {
                            Log::info('Error in GAME CONSUME payload');
                            Log::info($message->payload);
                            continue;
                        }
                        switch ($payload->command) {
                            case 'league':
                                $leaguesTransformationHandler->init($payload, $message->offset, $swoole)->handle();
                                break;
                            case 'event':
                                $eventsTransformationHandler->init($payload, $message->offset, $swoole)->handle();
                                break;
                            case 'odd':
                                $oddsValidationHandler->init($payload, $message->offset, $swoole)->handle();
                                break;
                            default:
                                break;
                        }
                        if (env('CONSUMER_PRODUCER_LOG', false)) {
                            Log::channel('kafkalog')->info(json_encode($message));
                        }
                        usleep(10000);
                        $kafkaConsumer->commitAsync($message);
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
