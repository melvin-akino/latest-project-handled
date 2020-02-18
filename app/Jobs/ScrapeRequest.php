<?php

namespace App\Jobs;

use Illuminate\Support\Facades\DB;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use RdKafka\Conf as KafkaConf;
use RdKafka\Producer;

abstract class ScrapeRequest extends CronJob
{
    protected $providers;
    protected $sports;
    protected $topic;

    public function __construct()
    {
        parent::__construct();
        $this->providers = DB::connection(config('database.crm_default'))->table('providers')->where('is_enabled',
            true)->get()->toArray();
        $this->sports = DB::table('sports')->where('is_enabled', true)->get()->toArray();
        $kafka = new Producer($this->getConfig());
        $this->topic = $kafka->newTopic(env('KAFKA_SCRAPE_REQUEST', 'scrape_req'));
    }

    public function interval()
    {
        return 1000;
    }

    public function isImmediate()
    {
        return true;
    }

    public function run()
    {
        foreach ($this->providers as $provider) {
            foreach ($this->sports as $sport) {
                $prePayload = [
                    'request_uid' => uniqid(),
                    'request_ts'  => $this->milliseconds(),
                    'command'     => 'odd',
                    'sub_command' => 'scrape',
                ];
                $prePayload['data'] = [
                    'provider' => strtolower($provider->alias),
                    'schedule' => $this->scheduleType,
                    'sport'    => $sport->id
                ];

                $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($prePayload));
            }
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

    protected function milliseconds() {
        $mt = explode(' ', microtime());
        return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
    }
}
