<?php

namespace App\Console\Commands;

use App\Events\ProcessedOdds;
use App\Jobs\ProcessScrapedData;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RdKafka\Conf as KafkaConf;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;
use RdKafka\Message;
use SwooleTW\Http\Websocket\Facades\Websocket;
use swoole_websocket_server;

class KafkaPublisher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:publish {message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kafka publisher';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $message = $this->argument('message');
        $kafkaProducer = new Producer($this->getConfig());

        $topic = $kafkaProducer->newTopic('alex-scraping');
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $message);
    }

    protected function getConfig()
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
