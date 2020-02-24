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

        /*$kafkaConsumer = new KafkaConsumer(self::getConfig());
        $kafkaConsumer->subscribe([env('KAFKA_SCRAPE_ODDS')]);
        while (!self::$quit) {
            $message = $kafkaConsumer->consume(120 * 1000);
            if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                *
                 * @TODO Dispatch Jobs to do the transformation
                 * Log::debug(json_encode($message));
                 * $kafkaTable->set('message:' . $message->offset, ['value' => $message->payload]);
                 * Log::debug(json_encode($kafkaTable->get('message:' . $message->offset)));

                TransformKafkaMessage::dispatch($message);

                $kafkaConsumer->commitAsync($message);
            } else {
                Log::error(json_encode([$message]));
            }
        }*/
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

    private static function testData()
    {
        return json_encode([
            'request_uid' => '0eb7273d-07bc-4773-a4a2-1e193c9ac92e',
            'request_ts'  => '1378761833768',
            'command'     => 'odd',
            'sub_command' => 'transform',
            'data'        => [
                'provider'          => 'hg',
                'type'              => 'inplay',
                'sportId'           => 1,
                'leagueName'        => 'Australia Tasmania Summer Cup',
                'homeTeam'          => 'Glenorchy Knights',
                'awayTeam'          => 'Kingborough Lions United',
                'referenceSchedule' => '2020-02-13T00:30:00.000+04:00',
                'running_time'      => '2H 20:58',
                'home_score'        => '0',
                'away_score'        => '0',
                'home_redcard'      => '0',
                'away_redcard'      => '0',
                'id'                => 8,
                'events'            => [
                    [
                        'eventId'     => '4044820',
                        'market_type' => 1,
                        'market_odds' => [
                            [
                                'oddsType'        => '1X2',
                                'marketSelection' => [
                                    [
                                        'market_id' => 'RMH4044819',
                                        'indicator' => 'Home',
                                        'odds'      => '2.19',
                                    ],
                                    [
                                        'market_id' => 'RMC4044819',
                                        'indicator' => 'Away',
                                        'odds'      => '4.35',
                                    ],
                                    [
                                        'market_id' => 'RMN4044819',
                                        'indicator' => 'Draw',
                                        'odds'      => '2.31',
                                    ],
                                ],
                            ],
                            [
                                'oddsType'        => 'HDP',
                                'marketSelection' => [
                                    [
                                        'market_id' => 'REH4044819',
                                        'indicator' => 'Home',
                                        'odds'      => '0.810',
                                        'points'    => '-0.25',
                                    ],
                                    [
                                        'market_id' => 'REC4044819',
                                        'indicator' => 'Away',
                                        'odds'      => '1.010',
                                        'points'    => '+0.25',
                                    ],
                                ],
                            ],
                            [
                                'oddsType'        => 'OU',
                                'marketSelection' => [
                                    [
                                        'market_id' => 'ROUC4044819',
                                        'indicator' => 'Home',
                                        'odds'      => '1.020',
                                        'points'    => '1.25',
                                    ],
                                    [
                                        'market_id' => 'ROUH4044819',
                                        'indicator' => 'Away',
                                        'odds'      => '0.780',
                                        'points'    => '1.25',
                                    ],
                                ],
                            ],
                            [
                                'oddsType'        => 'OE',
                                'marketSelection' => [
                                    [
                                        'market_id' => 'EOO4044819',
                                        'indicator' => 'Home',
                                        'odds'      => '2.04',
                                    ],
                                    [
                                        'market_id' => 'EOE4044819',
                                        'indicator' => 'Away',
                                        'odds'      => '1.82',
                                    ],
                                ],
                            ],
                            [
                                'oddsType'        => 'HT 1X2',
                                'marketSelection' => [],
                            ],
                            [
                                'oddsType'        => 'HT HDP',
                                'marketSelection' => [],
                            ],
                            [
                                'oddsType'        => 'HT OU',
                                'marketSelection' => [],
                            ],
                        ],
                    ],
                    [
                        [
                            'eventId'     => '4044822',
                            'market_type' => 2,
                            'market_odds' => [
                                [
                                    'oddsType'        => '1X2',
                                    'marketSelection' => [],
                                ],
                                [
                                    'oddsType'        => 'HDP',
                                    'marketSelection' => [
                                        [
                                            'market_id' => 'REH4044821',
                                            'indicator' => 'Home',
                                            'odds'      => '1.150',
                                            'points'    => '-0.5',
                                        ],
                                        [
                                            'market_id' => 'REC4044821',
                                            'indicator' => 'Away',
                                            'odds'      => '0.670',
                                            'points'    => '+0.5',
                                        ],
                                    ],
                                ],
                                [
                                    'oddsType'        => 'OU',
                                    'marketSelection' => [
                                        [
                                            'market_id' => 'ROUC4044821',
                                            'indicator' => 'Home',
                                            'odds'      => '0.610',
                                            'points'    => '1.0',
                                        ],
                                        [
                                            'market_id' => 'ROUH4044821',
                                            'indicator' => 'Away',
                                            'odds'      => '1.190',
                                            'points'    => '1.0',
                                        ],
                                    ],
                                ],
                                [
                                    'oddsType'        => 'OE',
                                    'marketSelection' => [],
                                ],
                                [
                                    'oddsType'        => 'HT 1X2',
                                    'marketSelection' => [],
                                ],
                                [
                                    'oddsType'        => 'HT HDP',
                                    'marketSelection' => [],
                                ],
                                [
                                    'oddsType'        => 'HT OU',
                                    'marketSelection' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
