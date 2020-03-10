<?php

namespace App\Processes;

use App\Tasks\{TransformKafkaMessageEvents, TransformKafkaMessageLeagues, TransformKafkaMessageOdds};
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
                    env('KAFKA_SCRAPE_EVENTS', 'SCRAPING-PROVIDER-EVENTS')
                ]);
echo 1;
                $baseTime = time();
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
                            default:
                                if (!isset($payload->data->events)) {
                                    Log::info("Transformation ignored - No Event Found");
                                    echo '*';
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
                                $time = time();
                                if ($swoole->wsTable->exist('test:' . $time)) {
                                    $value = $swoole->wsTable->get('test:' . $time)['value'];
                                    $swoole->wsTable->set('test:' . $time, ['value' => ++$value]);
                                } else {
                                    $value = 1;
                                    $swoole->wsTable->set('test:' . $time, ['value' => $value]);
                                }
                                var_dump(($time - $baseTime) . 's = ' . $value);
                                Task::deliver(new TransformKafkaMessageOdds($payload));
                                break;
                        }

                        $kafkaConsumer->commitAsync($message);
                    } else {
                        Log::error(json_encode([$message]));
                    }

                    self::getUpdatedOdds($swoole);
                    self::getAdditionalLeagues($swoole);
                    self::getForRemovallLeagues($swoole);
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

    private static function getUpdatedOdds($swoole)
    {
        $table = $swoole->wsTable;
        foreach ($table as $k => $r) {
            if (strpos($k, 'updatedEvents:') === 0) {
                foreach ($table as $key => $row) {
                    $updatedMarkets = json_decode($r['value']);
                    if (!empty($updatedMarkets)) {
                        if (strpos($key, 'fd:') === 0) {
                            $fd = $table->get('uid:' . $row['value']);
                            $swoole->push($fd['value'], json_encode(['getUpdatedOdds' => $updatedMarkets]));
                            $table->del($k);
                        }
                    }
                }
            }
        }
    }

    private static function getAdditionalLeagues($swoole)
    {
        $abbr    = "add";
        $part    = strtolower('ADDITIONAL');
        $swtKey  = "::LEAGUE_" . strtoupper($part);
        $topic   = "getAdditionalLeagues";
        $table   = $swoole->wsTable;

        foreach ($table as $key => $row) {
            if (strpos($key, 'fd:') === 0) {
                $fd = $table->get('uid:' . $row['value']);

                foreach ($table AS $_key => $_row) {
                    if (strpos($_key, $swtKey) > -1) {
                        $data = json_decode($table[$_key]['value']);

                        if (!empty($data)) {
                            $swoole->push($fd['value'], json_encode([ $topic => $data->{$abbr} ]));
                            $table->del($_key);
                        }
                    }
                }
            }
        }
    }

    private static function getForRemovallLeagues($swoole)
    {
        $abbr    = "rmv";
        $part    = strtolower('removal');
        $swtKey  = "::LEAGUE_" . strtoupper($part);
        $topic   = "getForRemovalLeagues";
        $table   = $swoole->wsTable;
        $slTable = $swoole->userSelectedLeaguesTable;

        foreach ($table as $key => $row) {
            if (strpos($key, 'fd:') === 0) {
                $fd = $table->get('uid:' . $row['value']);

                foreach ($table AS $_key => $_row) {
                    if (strpos($_key, $swtKey) > -1) {
                        $data = json_decode($table[$_key]['value']);

                        if (!empty($data)) {
                            $swoole->push($fd['value'], json_encode([ $topic => $data->{$abbr} ]));
                            $table->del($_key);

                            foreach ($slTable AS $slKey => $slRow) {
                                foreach ($data->{$abbr} AS $_abbr) {
                                    if ((strpos($slKey, 'userId:' . $row['value']) > -1) && (strpos($slKey, ':schedule:' . $_abbr->schedule . ':uniqueId:') > -1)) {
                                        if ($slTable[$slKey]['league_name'] == $_abbr->name) {
                                            $slTable->del($slKey);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
