<?php

namespace App\Processes;

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
    const KAFKA_TOPIC = 'alex-scraping';

    public static function callback(Server $swoole, Process $process)
    {
        $kafkaTable = $swoole->kafkaTable;

        $kafkaConsumer = new KafkaConsumer(self::getConfig());
        $kafkaConsumer->subscribe([self::KAFKA_TOPIC]);
        while (!self::$quit) {
            $message = $kafkaConsumer->consume(120 * 1000);
            if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
                Log::error(json_encode($message));
                $kafkaTable->set('message:' . $message->offset, ['value' => $message->payload]);
                Log::error(json_encode($kafkaTable->get('message:' . $message->offset)));
//                Log::error(json_encode($kafkaTable->get('message')));
//                $this->info(json_encode($message));
//                Redis::set(self::REDIS_KAFKA, json_encode([
//                    self::KAFKA_TOPIC => $message->payload
//                ]));
//                var_dump(Redis::get(self::REDIS_KAFKA));
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
}
