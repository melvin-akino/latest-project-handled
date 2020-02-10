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
    protected $signature = 'kafka:publish';

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
// do your process
    }
}
