<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use App\Tasks\TransformKafkaMessageLeagues;

class KafkaConsumeLeagues implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            if ($swoole->wsTable->exist('data2Swt')) {
                $kafkaTable    = $swoole->kafkaTable;

                $kafkaConsumer = resolve('KafkaConsumer');
                $kafkaConsumer->subscribe([env('KAFKA_SCRAPE_LEAGUES')]);
                while (!self::$quit) {
                    $message = $kafkaConsumer->consume(120 * 1000);
                    if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                        $kafkaTable->set('message:' . $message->offset, ['value' => $message->payload]);

                        Task::deliver(new TransformKafkaMessageLeagues($message));

                        $kafkaConsumer->commitAsync($message);
                    } else {
                        Log::error(json_encode([$message]));
                    }
                    self::getAdditionalLeagues($swoole);
                    self::getForRemovallLeagues($swoole);
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
