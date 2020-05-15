<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
 
 
class StartKafaScrapingOdds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'StartKafaScrapingOdds:session';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will start a Kafka scraping odds debugging tool';

    

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    
    public function message($message)
    {
        $payload = json_decode($message->payload);
        $leagueName = $payload->data->leagueName;
        $homeTeam = $payload->data->homeTeam;
        $awayTeam = $payload->data->awayTeam;
        $provider = $payload->data->provider;
        $sport = $payload->data->sport;
        $redis_smember = $leagueName .' -' .$homeTeam .'-'. $awayTeam .'-' . $provider .'-'. $sport;
        echo $redis_smember;
        $redis_smember = str_replace(" ","",$redis_smember);
        $members = Redis::sadd('REDIS-SCRAPING-ODDS',$redis_smember);
        $old = Redis::hget($redis_smember,'previous');
        $new =Redis::hget($redis_smember,'latest');
        if ($old ==false){
            Redis::hmset($redis_smember,'latest', $message->payload);
            Redis::hmset($redis_smember,'previous', $message->payload);   
        } else {
            Redis::hmset($redis_smember,'previous', $new);
            Redis::hmset($redis_smember,'latest', $message->payload);

        }
        

          #smembers
          #hgetall
          #hset
          # hmset gameesched today 123
          #  hget Chile-PrimeraDivision-UnionLaCalera-Coquimbo latest
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $conf = new \RdKafka\Conf();
        $conf->set('group.id', 'multiline_KafkaMon');

        $rk = new \RdKafka\Consumer($conf);
        $rk->addBrokers('192.168.10.37:9092');

        $topicConf = new \RdKafka\TopicConf();
        $topicConf->set('auto.commit.interval.ms', 100);
        $topicConf->set('offset.store.method', 'broker');
        $topicConf->set('auto.offset.reset', 'latest');
        $queue = $rk->newQueue();
        $topic = $rk->newTopic("JAN-SCRAPING-ODDS", $topicConf);
        $topic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);
        while (true) {
             $message=$queue->consume(120 * 10000);

             switch($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                        //echo $message->payload;
                        $this->message($message);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        echo "No more messages; will wait for more\n";
                     break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        echo "Timed out\n";
                    break;
                default:
                        throw new Exception($message->errstr(), $message->err);
                    break;
            
            }
        }
    

    }
}
