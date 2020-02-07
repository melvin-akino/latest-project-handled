<?php

namespace App\Console\Commands;

use App\Events\ProcessedOdds;
use App\Jobs\ProcessScrapedData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RdKafka\Conf as KafkaConf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use SwooleTW\Http\Websocket\Facades\Websocket;
SwooleTW\Http\Server\Facades\Server;

class KafkaData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kafka consumer';

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
//        $kafkaConsumer = new KafkaConsumer($this->getConfig());
//
//        $kafkaConsumer->subscribe(['alex-scraping']);
//
//        while (true) {
//            $message = $kafkaConsumer->consume(120 * 1000);
//            if ($message->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
//                $this->info(json_encode($message));
//                $this->processScrapedMessage($message);
//                $kafkaConsumer->commitAsync($message);
//            } else {
//                //log error
//            }
//        }
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

    /**
     * Process Kafka message
     *
     * @return void
     */
    protected function processScrapedMessage(Message $kafkaMessage)
    {
        Websocket::broadcast()->emit('changeOdds', $kafkaMessage);
        $message = $this->decodeKafkaMessage($kafkaMessage);
//        Websocket::broadcast()->emit('changeOdds', 'asdasd');
//            event(new ProcessedOdds(['asdasd']));
            //code here if any
//        });


    }

    /**
     * Decode kafka message
     *
     * @param \RdKafka\Message $kafkaMessage
     * @return object
     */
    protected function decodeKafkaMessage(Message $kafkaMessage)
    {
        return $kafkaMessage;
    }
}
