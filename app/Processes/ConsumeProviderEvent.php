<?php

namespace App\Processes;

use App\Jobs\TransformProviderEvent;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RdKafka\KafkaConsumer;
use RdKafka\Conf as KafkaConf;
use Swoole\Http\Request;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;

class ConsumeProviderEvent implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        $kafkaConsumer = new KafkaConsumer(self::getConfig());
        $kafkaConsumer->subscribe([env('KAFKA_SCRAPE_PROVIDER_EVENTS', 'SCRAPING-PROVIDER-EVENTS')]);

        while (!self::$quit) {
            $message = $kafkaConsumer->consume(3 * 1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    TransformProviderEvent::dispatch((object) ['payload' => $message]);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    sleep(2);
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    echo $message->errstr() . PHP_EOL;
                    break;
                default:
                    throw new Exception($message->errstr(), $message->err);
                    break;
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

        $conf->set('group.id', 'multiline');

        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));

        $conf->set('auto.offset.reset', 'smallest');

        $conf->set('enable.auto.commit', 'false');

        if (env('KAFKA_DEBUG', false)) {
            $conf->set('log_level', LOG_DEBUG);
            $conf->set('debug', 'all');
        }

        return $conf;
    }
}
