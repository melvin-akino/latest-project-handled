<?php

namespace App\Processes;

use App\Models\SystemConfiguration;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Coroutine;
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
                $toLogs = [
                    "class"       => "GameConsume",
                    "message"     => "Initiating...",
                    "module"      => "PROCESS",
                    "status_code" => 102,
                ];
                monitorLog('monitor_process', 'info', $toLogs);

                $kafkaConsumer = app('KafkaConsumer');
                $kafkaConsumer->subscribe([
                    env('KAFKA_SCRAPE_ODDS', 'SCRAPING-ODDS'),
                    env('KAFKA_SCRAPE_EVENTS', 'SCRAPING-PROVIDER-EVENTS'),
                    env('KAFKA_SCRAPE_LEAGUES', 'SCRAPING-PROVIDER-LEAGUES')
                ]);

                $oddsValidationHandler       = app('OddsValidationHandler');
                $eventsTransformationHandler = app('EventsTransformationHandler');
                $leaguesTransformationHandler = app('LeaguesTransformationHandler');

                $missingCountConfiguration = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT');

                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(0);
                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $payload = json_decode($message->payload);
                        if (!isset($payload->command)) {
                            $toLogs = [
                                "class"       => "GameConsume",
                                "message"     => [
                                    "payload_error" => $message->payload
                                ],
                                "module"      => "PROCESS_ERROR",
                                "status_code" => 400,
                            ];
                            monitorLog('monitor_process', 'error', $toLogs);

                            continue;
                        }
                        switch ($payload->command) {
                            case 'league':
                                $leaguesTransformationHandler->init($payload, $message->offset, $swoole)->handle();
                                break;
                            case 'event':
                                $eventsTransformationHandler->init($payload, $message->offset, $swoole, $missingCountConfiguration)->handle();
                                break;
                            case 'odd':
                                $oddsValidationHandler->init($payload, $message->offset, $swoole)->handle();
                                break;
                            default:
                                break;
                        }
                        if (env('CONSUMER_PRODUCER_LOG', false)) {
                            $toLogs = [
                                "class"       => "GameConsume",
                                "message"     => $message,
                                "module"      => "PROCESS_ERROR",
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
                "class"       => "GameConsume",
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
