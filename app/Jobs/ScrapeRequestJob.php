<?php

namespace App\Tasks;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use RdKafka\{Conf as KafkaConf, Producer};

class ScrapeRequestJob
{
    use Dispatchable;

    private $provider;
    private $scheduleType;
    private $topic;

    public function __construct(string $scheduleType, object $provider)
    {
        $kafka = new Producer($this->getConfig());
        $this->topic = $kafka->newTopic(env('KAFKA_SCRAPE_REQUEST', 'scrape_req'));

        $this->provider = $provider;
        $this->scheduleType = $scheduleType;
    }

    public function handle()
    {
        $sports = DB::table('sports')->where('is_enabled', true)->get()->toArray();
        foreach ($sports as $sport) {
            $prePayload = [
                'request_uid' => uniqid(),
                'request_ts'  => $this->milliseconds(),
                'command'     => 'odd',
                'sub_command' => 'scrape',
            ];
            $prePayload['data'] = [
                'provider' => strtolower($this->provider->alias),
                'schedule' => $this->scheduleType,
                'sport'    => $sport->id
            ];

            $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($prePayload));
        }
    }

    private function getConfig()
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

    private function milliseconds()
    {
        $mt = explode(' ', microtime());
        return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
    }
}
