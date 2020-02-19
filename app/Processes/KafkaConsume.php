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
        Data2SWT::dispatch();
        TransformKafkaMessage::dispatch(self::testData());

        $kafkaConsumer = new KafkaConsumer(self::getConfig());
        $kafkaConsumer->subscribe([env('KAFKA_SCRAPE_ODDS')]);
        while (!self::$quit) {
            $message = $kafkaConsumer->consume(120 * 1000);
            if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                /**
                 * @TODO Dispatch Jobs to do the transformation
                 * Log::debug(json_encode($message));
                 * $kafkaTable->set('message:' . $message->offset, ['value' => $message->payload]);
                 * Log::debug(json_encode($kafkaTable->get('message:' . $message->offset)));
                 */
                TransformKafkaMessage::dispatch($message);

                $kafkaConsumer->commitAsync($message);
            } else {
                Log::error(json_encode([$message]));
            }
        }
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

    private static function testData() {
        return json_encode(array (
            'provider' => 'hg',
            'command' => 'odd',
            'call' => 'request',
            'schedule' => 'early',
            'sportId' => 1,
            'request_uid' => 123456789,
            'request_ts' => 987654321,
            'data' =>
                array (
                    'leagueName' => 'Mexico Cup',
                    'homeTeam' => 'Dorados De Sinaloa',
                    'awayTeam' => 'Juarez',
                    'referenceSchedule' => '2020-02-13 08:00:00',
                    'running_time' => '',
                    'home_score' => 0,
                    'away_score' => 0,
                    'home_redcard' => 0,
                    'away_redcard' => 0,
                    'event_since' => 0,
                    'id' => 127,
                    'events' =>
                        array (
                            0 =>
                                array (
                                    'eventId_ft' => '4036035',
                                    'eventId_ht' => '4036036',
                                    'gnum_h' => '31182',
                                    'gnum_c' => '31181',
                                    'odd_since' => 0,
                                    'market_type' => 1,
                                    'market_odds' =>
                                        array (
                                            0 =>
                                                array (
                                                    'oddsType' => 'FT 1X2',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'MH4036035',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '2.42',
                                                                    'type' => 'H',
                                                                    'rtype' => 'MH',
                                                                    'wtype' => 'M',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'MC4036035',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '2.65',
                                                                    'type' => 'C',
                                                                    'rtype' => 'MC',
                                                                    'wtype' => 'M',
                                                                ),
                                                            2 =>
                                                                array (
                                                                    'market_id' => 'MN4036035',
                                                                    'indicator' => 'Draw',
                                                                    'odds' => '3.25',
                                                                    'type' => 'N',
                                                                    'rtype' => 'MN',
                                                                    'wtype' => 'M',
                                                                ),
                                                        ),
                                                ),
                                            1 =>
                                                array (
                                                    'oddsType' => 'FT HDP',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'RH4036035',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '0.850',
                                                                    'points' => '0.0',
                                                                    'type' => 'H',
                                                                    'rtype' => 'RH',
                                                                    'wtype' => 'R',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'RC4036035',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '1.030',
                                                                    'points' => '0.0',
                                                                    'type' => 'C',
                                                                    'rtype' => 'RC',
                                                                    'wtype' => 'R',
                                                                ),
                                                        ),
                                                ),
                                            2 =>
                                                array (
                                                    'oddsType' => 'FT OU',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'OUC4036035',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '0.970',
                                                                    'points' => '2.25',
                                                                    'type' => 'C',
                                                                    'rtype' => 'OUC',
                                                                    'wtype' => 'OU',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'OUH4036035',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '0.890',
                                                                    'points' => '2.25',
                                                                    'type' => 'H',
                                                                    'rtype' => 'OUH',
                                                                    'wtype' => 'OU',
                                                                ),
                                                        ),
                                                ),
                                            3 =>
                                                array (
                                                    'oddsType' => 'FT OE',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'EOO4036035',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '1.95',
                                                                    'type' => 'ODD',
                                                                    'rtype' => 'ODD',
                                                                    'wtype' => 'EO',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'EOE4036035',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '1.92',
                                                                    'type' => 'EVEN',
                                                                    'rtype' => 'EVEN',
                                                                    'wtype' => 'EO',
                                                                ),
                                                        ),
                                                ),
                                            4 =>
                                                array (
                                                    'oddsType' => 'HT 1X2',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'HMH4036036',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '3.05',
                                                                    'type' => 'H',
                                                                    'rtype' => 'HMH',
                                                                    'wtype' => 'HM',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'HMC4036036',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '3.40',
                                                                    'type' => 'C',
                                                                    'rtype' => 'HMC',
                                                                    'wtype' => 'HM',
                                                                ),
                                                            2 =>
                                                                array (
                                                                    'market_id' => 'HMN4036036',
                                                                    'indicator' => 'Draw',
                                                                    'odds' => '2.01',
                                                                    'type' => 'N',
                                                                    'rtype' => 'HMN',
                                                                    'wtype' => 'HM',
                                                                ),
                                                        ),
                                                ),
                                            5 =>
                                                array (
                                                    'oddsType' => 'HT HDP',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'HRH4036036',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '0.830',
                                                                    'points' => '0.0',
                                                                    'type' => 'H',
                                                                    'rtype' => 'HRH',
                                                                    'wtype' => 'HR',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'HRC4036036',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '1.050',
                                                                    'points' => '0.0',
                                                                    'type' => 'C',
                                                                    'rtype' => 'HRC',
                                                                    'wtype' => 'HR',
                                                                ),
                                                        ),
                                                ),
                                            6 =>
                                                array (
                                                    'oddsType' => 'HT OU',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'HOUC4036036',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '1.120',
                                                                    'points' => '1.0',
                                                                    'type' => 'C',
                                                                    'rtype' => 'HOUC',
                                                                    'wtype' => 'HOU',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'HOUH4036036',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '0.740',
                                                                    'points' => '1.0',
                                                                    'type' => 'H',
                                                                    'rtype' => 'HOUH',
                                                                    'wtype' => 'HOU',
                                                                ),
                                                        ),
                                                ),
                                        ),
                                ),
                            1 =>
                                array (
                                    'eventId_ft' => '4036037',
                                    'eventId_ht' => '4036038',
                                    'gnum_h' => '31184',
                                    'gnum_c' => '31183',
                                    'market_type' => 2,
                                    'market_odds' =>
                                        array (
                                            0 =>
                                                array (
                                                    'oddsType' => 'FT 1X2',
                                                    'marketSelection' =>
                                                        array (
                                                        ),
                                                ),
                                            1 =>
                                                array (
                                                    'oddsType' => 'FT HDP',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'RH4036037',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '1.150',
                                                                    'points' => '-0.25',
                                                                    'type' => 'H',
                                                                    'rtype' => 'RH',
                                                                    'wtype' => 'R',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'RC4036037',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '0.730',
                                                                    'points' => '+0.25',
                                                                    'type' => 'C',
                                                                    'rtype' => 'RC',
                                                                    'wtype' => 'R',
                                                                ),
                                                        ),
                                                ),
                                            2 =>
                                                array (
                                                    'oddsType' => 'FT OU',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'OUC4036037',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '1.180',
                                                                    'points' => '2.5',
                                                                    'type' => 'C',
                                                                    'rtype' => 'OUC',
                                                                    'wtype' => 'OU',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'OUH4036037',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '0.680',
                                                                    'points' => '2.5',
                                                                    'type' => 'H',
                                                                    'rtype' => 'OUH',
                                                                    'wtype' => 'OU',
                                                                ),
                                                        ),
                                                ),
                                            3 =>
                                                array (
                                                    'oddsType' => 'FT OE',
                                                    'marketSelection' =>
                                                        array (
                                                        ),
                                                ),
                                            4 =>
                                                array (
                                                    'oddsType' => 'HT 1X2',
                                                    'marketSelection' =>
                                                        array (
                                                        ),
                                                ),
                                            5 =>
                                                array (
                                                    'oddsType' => 'HT HDP',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'HRH4036038',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '1.350',
                                                                    'points' => '-0.25',
                                                                    'type' => 'H',
                                                                    'rtype' => 'HRH',
                                                                    'wtype' => 'HR',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'HRC4036038',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '0.530',
                                                                    'points' => '+0.25',
                                                                    'type' => 'C',
                                                                    'rtype' => 'HRC',
                                                                    'wtype' => 'HR',
                                                                ),
                                                        ),
                                                ),
                                            6 =>
                                                array (
                                                    'oddsType' => 'HT OU',
                                                    'marketSelection' =>
                                                        array (
                                                            0 =>
                                                                array (
                                                                    'market_id' => 'HOUC4036038',
                                                                    'indicator' => 'Home',
                                                                    'odds' => '0.690',
                                                                    'points' => '0.75',
                                                                    'type' => 'C',
                                                                    'rtype' => 'HOUC',
                                                                    'wtype' => 'HOU',
                                                                ),
                                                            1 =>
                                                                array (
                                                                    'market_id' => 'HOUH4036038',
                                                                    'indicator' => 'Away',
                                                                    'odds' => '1.170',
                                                                    'points' => '0.75',
                                                                    'type' => 'H',
                                                                    'rtype' => 'HOUH',
                                                                    'wtype' => 'HOU',
                                                                ),
                                                        ),
                                                ),
                                        ),
                                ),
                        ),
                ),
        ));
    }
}
