<?php

namespace App\Processes;

use App\Jobs\Data2SWT;
use App\Jobs\TransformKafkaMessage;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RdKafka\KafkaConsumer;
use RdKafka\Conf as KafkaConf;
use Swoole\Http\Request;
use Swoole\Http\Server;
use Swoole\Process;

class KafkaConsume implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        $kafkaTable = $swoole->kafkaTable;

        sleep(2);
        TransformKafkaMessage::dispatch((object) ['payload' => self::testData()]);


//        $kafkaConsumer = new KafkaConsumer(self::getConfig());
//        $kafkaConsumer->subscribe([env('KAFKA_SCRAPE_ODDS')]);
//        while (!self::$quit) {
//            $message = $kafkaConsumer->consume(120 * 1000);
//            if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
//
//                 Log::debug(json_encode($message));
//                 $kafkaTable->set('message:' . $message->offset, ['value' => $message->payload]);
//                 Log::debug(json_encode($kafkaTable->get('message:' . $message->offset)));
//
//                TransformKafkaMessage::dispatch($message);
//
//                $kafkaConsumer->commitAsync($message);
//            } else {
//                Log::error(json_encode([$message]));
//            }
//
//            self::getAdditionalLeagues($swoole);
//            self::getForRemovallLeagues($swoole);
//            sleep(1);
//        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }

    private static function getConfig()
    {
        $conf = new KafkaConf();

        // Configure the group.id. All consumer with the same group.id will consume
        // different partitions.
        $conf->set('group.id', 'multiline');

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'smallest': start from the beginning
        $conf->set('auto.offset.reset', 'smallest');

        // Automatically and periodically commit offsets in the background
        $conf->set('enable.auto.commit', 'false');

        return $conf;
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

    private static function testData()
    {
        return "{\"request_uid\": \"77f61545-6756-4463-97a1-b7d8b3824cd9\", \"request_ts\": \"1573708436\", \"command\": \"odd\", \"sub_command\": \"transform\", \"data\": {\"provider\": \"hg\", \"schedule\": \"inplay\", \"sport\": 1, \"leagueName\": \"CONCACAF Champions League\", \"homeTeam\": \"Seattle Sounders\", \"awayTeam\": \"CD Olimpia\", \"referenceSchedule\": \"2020-02-27T19:00:00.000+04:00\", \"running_time\": \"2H 07:23\", \"home_score\": \"1\", \"away_score\": \"1\", \"home_redcard\": \"0\", \"away_redcard\": \"0\", \"id\": 2, \"events\": [{\"eventId\": \"4052313\", \"market_type\": 1, \"market_odds\": [{\"oddsType\": \"1X2\", \"marketSelection\": [{\"market_id\": \"RMH4052313\", \"indicator\": \"Home\", \"odds\": \"1.93\"}, {\"market_id\": \"RMC4052313\", \"indicator\": \"Away\", \"odds\": \"4.80\"}, {\"market_id\": \"RMN4052313\", \"indicator\": \"Draw\", \"odds\": \"2.50\"}]}, {\"oddsType\": \"HDP\", \"marketSelection\": [{\"market_id\": \"REH4052313\", \"indicator\": \"Home\", \"odds\": \"0.240\", \"points\": \"-0.5\"}, {\"market_id\": \"REC4052313\", \"indicator\": \"Away\", \"odds\": \"0.230\", \"points\": \"+0.5\"}]}, {\"oddsType\": \"OU\", \"marketSelection\": [{\"market_id\": \"ROUC4052313\", \"indicator\": \"Home\", \"odds\": \"0.880\", \"points\": \"O 3.25\"}, {\"market_id\": \"ROUH4052313\", \"indicator\": \"Away\", \"odds\": \"0.940\", \"points\": \"U 3.25\"}]}, {\"oddsType\": \"OE\", \"marketSelection\": [{\"market_id\": \"RODD4052313\", \"indicator\": \"Home\", \"odds\": \"O 2.03\"}, {\"market_id\": \"REVEN4052313\", \"indicator\": \"Away\", \"odds\": \"E 1.33\"}]}, {\"oddsType\": \"HT 1X2\", \"marketSelection\": [{\"market_id\": \"HRMH4052314\", \"indicator\": \"Home\", \"odds\": \"\"}, {\"market_id\": \"HRMC4052314\", \"indicator\": \"Away\", \"odds\": \"\"}, {\"market_id\": \"HRMC4052314\", \"indicator\": \"Draw\", \"odds\": \"\"}]}, {\"oddsType\": \"HT HDP\", \"marketSelection\": [{\"market_id\": \"HREH4052314\", \"indicator\": \"Home\", \"odds\": \"\", \"points\": \"\"}, {\"market_id\": \"HREC4052314\", \"indicator\": \"Away\", \"odds\": \"\", \"points\": \"\"}]}, {\"oddsType\": \"HT OU\", \"marketSelection\": [{\"market_id\": \"HROUC4052314\", \"indicator\": \"Home\", \"odds\": \"\", \"points\": \"\"}, {\"market_id\": \"HROUH4052314\", \"indicator\": \"Away\", \"odds\": \"\", \"points\": \"\"}]}]}, {\"eventId\": \"4052315\", \"market_type\": 2, \"market_odds\": [{\"oddsType\": \"1X2\", \"marketSelection\": [{\"market_id\": \"RMH4052315\", \"indicator\": \"Home\", \"odds\": \"\"}, {\"market_id\": \"RMC4052315\", \"indicator\": \"Away\", \"odds\": \"\"}, {\"market_id\": \"RMN4052315\", \"indicator\": \"Draw\", \"odds\": \"\"}]}, {\"oddsType\": \"HDP\", \"marketSelection\": [{\"market_id\": \"REH4052315\", \"indicator\": \"Home\", \"odds\": \"0.610\", \"points\": \"-0.25\"}, {\"market_id\": \"REC4052315\", \"indicator\": \"Away\", \"odds\": \"1.200\", \"points\": \"+0.25\"}]}, {\"oddsType\": \"OU\", \"marketSelection\": [{\"market_id\": \"ROUC4052315\", \"indicator\": \"Home\", \"odds\": \"1.150\", \"points\": \"O 3.5\"}, {\"market_id\": \"ROUH4052315\", \"indicator\": \"Away\", \"odds\": \"0.650\", \"points\": \"U 3.5\"}]}, {\"oddsType\": \"OE\", \"marketSelection\": [{\"market_id\": \"RODD4052315\", \"indicator\": \"Home\", \"odds\": \"\"}, {\"market_id\": \"REVEN4052315\", \"indicator\": \"Away\", \"odds\": \"\"}]}, {\"oddsType\": \"HT 1X2\", \"marketSelection\": [{\"market_id\": \"HRMH4052316\", \"indicator\": \"Home\", \"odds\": \"\"}, {\"market_id\": \"HRMC4052316\", \"indicator\": \"Away\", \"odds\": \"\"}, {\"market_id\": \"HRMC4052316\", \"indicator\": \"Draw\", \"odds\": \"\"}]}, {\"oddsType\": \"HT HDP\", \"marketSelection\": [{\"market_id\": \"HREH4052316\", \"indicator\": \"Home\", \"odds\": \"\", \"points\": \"\"}, {\"market_id\": \"HREC4052316\", \"indicator\": \"Away\", \"odds\": \"\", \"points\": \"\"}]}, {\"oddsType\": \"HT OU\", \"marketSelection\": [{\"market_id\": \"HROUC4052316\", \"indicator\": \"Home\", \"odds\": \"\", \"points\": \"\"}, {\"market_id\": \"HROUH4052316\", \"indicator\": \"Away\", \"odds\": \"\", \"points\": \"\"}]}]}]}}";
    }
}
