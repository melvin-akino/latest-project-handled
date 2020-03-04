<?php

namespace App\Processes;

use App\Jobs\TransformKafkaMessageLeagues;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;

class KafkaConsumeLeagues implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        if ($swoole->wsTable->exist('data2Swt')) {
            $kafkaTable    = $swoole->kafkaTable;
            $kafkaConsumer = resolve('KafkaConsumer');
            $kafkaConsumer->subscribe([env('KAFKA_SCRAPE_LEAGUES')]);

            while (!self::$quit) {
                $message = $kafkaConsumer->consume(120 * 1000);

                if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                    $kafkaTable->set('message:' . $message->offset, ['value' => $message->payload]);

                    TransformKafkaMessageLeagues::dispatch(json_encode($message));

                    $kafkaConsumer->commitAsync($message);
                } else {
                    Log::error(json_encode([$message]));
                }

                self::getAdditionalLeagues($swoole);
                self::getForRemovallLeagues($swoole);
            }
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
        $data    = [];
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
        $data    = [];
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
