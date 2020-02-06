<?php

namespace App\Jobs;

use App\Jobs\Middleware\RateLimited;
use App\Models\ScrapedData;
use RdKafka\KafkaConsumer;
use RdKafka\Conf as KafkaConf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class ProcessScrapedData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $scrapedData;
    protected $context;
    protected $consumer;

    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
//        $this->scrapedData = $scrapedData->withoutRelations();

        $this->consumer = new KafkaConsumer($this->getConfig());
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //

        $fooQueue = $this->context->createQueue('foo');

        $consumer = $this->context->createConsumer($fooQueue);

// Enable async commit to gain better performance (true by default since version 0.9.9).
//$consumer->setCommitAsync(true);

        $message = $consumer->receive();

// process a message
        Log::error('test', $message);

        $consumer->acknowledge($message);
    }

    public function middleware()
    {
        return [new RateLimited()];
    }

    protected function getConfig()
    {
        $conf = new KafkaConf();

        // Configure the group.id. All consumer with the same group.id will consume
        // different partitions.
        $conf->set('group.id', 'myConsumerGroup');

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
