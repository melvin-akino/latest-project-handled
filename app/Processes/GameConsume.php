<?php

namespace App\Processes;

use App\Jobs\{
    TransformKafkaMessageEvents,
    TransformKafkaMessageLeagues,
    TransformKafkaMessageOdds

};
//use App\Tasks\TransformKafkaMessageOdds;
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
                $kafkaConsumer = resolve('KafkaConsumer');
                $kafkaConsumer->subscribe([
                    env('KAFKA_SCRAPE_ODDS', 'SCRAPING-ODDS'),
                    env('KAFKA_SCRAPE_LEAGUES', 'SCRAPING-PROVIDER-LEAGUES'),
                    env('KAFKA_SCRAPE_EVENTS', 'SCRAPING-PROVIDER-EVENTS')
                ]);

                Log::info("Game Consume Starts");
                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(0);
                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $payload = json_decode($message->payload);

                        switch ($payload->command) {
                            case 'league':
                                Log::info("TransformKafkaMessageLeagues");
                                TransformKafkaMessageLeagues::dispatch($payload);
                                break;
                            case 'event':
                                Log::info("TransformKafkaMessageEvents");
                                TransformKafkaMessageEvents::dispatch($payload);
                                break;
                            case 'odd':
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
                                Log::info("TransformKafkaMessageOdds called");
                                
                                //$odds = new TransformKafkaMessageOdds($payload);
                                //$this->dispatch($odds);
                                //$odds->dispatch();
                                
                                
                                $swooled = new \stdClass();
                                $swooled->providersTable = $swoole->providersTable;
                                $swooled->sportsTable = $swoole->sportsTable;
                                $swooled->leaguesTable = $swoole->leaguesTable;
                                $swooled->teamsTable = $swoole->teamsTable;
                                $swooled->eventsTable = $swoole->eventsTable;
                                $swooled->oddTypesTable = $swoole->oddTypesTable;
                                $swooled->sportOddTypesTable = $swoole->sportOddTypesTable;
                                $swooled->eventMarketsTable = $swoole->eventMarketsTable;
                                $swooled->leagueLookUpTable = $swoole->leagueLookUpTable;
                                $swooled->teamLookUpTable = $swoole->teamLookUpTable;
                                $swooled->eventScheduleChangeTable = $swoole->eventScheduleChangeTable;
                                
                                
                                
                                TransformKafkaMessageOdds::dispatch($payload,$swooled );
                                //Task::deliver(new TransformKafkaMessageOdds($payload));
                                break;
                            default:
                                break;
                        }
                        $kafkaConsumer->commitAsync($message);
                        Log::channel('kafkalog')->info(json_encode($message));
                        usleep(100);
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
