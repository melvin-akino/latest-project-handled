<?php

namespace App\Processes;

use App\Jobs\TransformKafkaMessageOdds;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Server;
use Swoole\Process;

class KafkaConsumeOdds implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        if ($swoole->wsTable->exist('data2Swt')) {
            $kafkaTable = $swoole->kafkaTable;

            $kafkaConsumer = resolve('KafkaConsumer');
            $kafkaConsumer->subscribe([env('KAFKA_SCRAPE_ODDS')]);
            while (!self::$quit) {
                $message = $kafkaConsumer->consume(120 * 1000);
                if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                    $kafkaTable->set('message:' . $message->offset, ['value' => $message->payload]);

                    TransformKafkaMessageOdds::dispatch($message);

                    $kafkaConsumer->commitAsync($message);
                } else {
                    Log::error(json_encode([$message]));
                }

                self::getAdditionalLeagues($swoole);
                self::getForRemovallLeagues($swoole);
                self::getUpdatedOdds($swoole);
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
        $leaguesData = [];
        $table = $swoole->wsTable;
        foreach ($table as $key => $row) {
            if (strpos($key, 'fd:') === 0) {
                $sports = $swoole->sportsTable;
                foreach ($sports as $sport) {
                    if ($swoole->wsTable->exist('userAdditionalLeagues:' . $row['value'] . ':sportId:' . $sport['id'])) {
                        $userAdditionalLeague = $swoole->wsTable->get('userAdditionalLeagues:' . $row['value'] . ':sportId:' . $sport['value']);
                        $leagues = $swoole->leaguesTable;
                        foreach ($leagues as $key => $league) {
                            if (strpos($key, $sport['value'] . ':') === 0) {
                                if ($league['timestamp'] > $userAdditionalLeague['value']) {
                                    $leaguesData[] = [
                                        'name'        => $league['multi_league'],
                                        'match_count' => $league['match_count']
                                    ];
                                }
                            }
                        }
                    }
                }
                if (!empty($leaguesData)) {
                    $fd = $swoole->wsTable->get('uid:' . $row['value']);
                    $swoole->push($fd['value'], json_encode(['getAdditionalLeagues' => $leaguesData]));
                }
            }
        }
    }

    private static function getForRemovallLeagues($swoole)
    {
        $leagues = [];
        $table = $swoole->wsTable;
        foreach ($table as $key => $row) {
            if (strpos($key, 'fd:') === 0) {
                $sports = $swoole->sportsTable;
                foreach ($sports as $sport) {
                    $deletedLeagues = $swoole->deletedLeaguesTable;
                    foreach ($deletedLeagues as $key => $league) {
                        $leagues[] = [
                            'league' => str_replace('sportId:' . $sport['value'] . ':league:',
                                '', $key)
                        ];
                    }
                }
                if (!empty($leagues)) {
                    $fd = $swoole->wsTable->get('uid:' . $row['value']);
                    $swoole->push($fd['value'], json_encode(['getForRemovalLeagues' => $leagues]));
                }
            }
        }
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
}
