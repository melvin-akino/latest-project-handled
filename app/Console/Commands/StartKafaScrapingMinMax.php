<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Exception;
 
class StartKafaScrapingMinMax extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'StartKafaScrapingMinMax:session';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will start a Kafka scraping minmax debugging tool';

    

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
        try {

            $payload            = json_decode($message->payload);
            $market_id          = $payload->data->market_id;
            $provider           = $payload->data->provider;
            $sport              = $payload->data->sport;
            $redisTopic         = env('REDIS_TOOL_MINMAX', 'REDIS-MON-TOOL-MINMAX-ODDS');
            $redisExpiration    = env('REDIS_TOOL_MINMAX_EXPIRE', 120);
            $redis_smember      = $market_id . ' -' . $provider .'-'. $sport;        
            $redis_smember      = str_replace(" ","",$redis_smember);
            # create redis ttl expiration 
            $ttl = Redis::ttl($redisTopic);

            if ($ttl < 0) Redis::expire($redisTopic, $redisExpiration);

            # add to redis member
            $members = Redis::sadd($redisTopic, $redis_smember);
             # hget parameters get data from members via key
            $old = Redis::hget($redis_smember, 'previous'); 
            $new = Redis::hget($redis_smember, 'latest'); 
            if ($old == false){
                # hmeset store data to redis member
                Redis::hmset($redis_smember, 'latest', $message->payload);  
                Redis::hmset($redis_smember, 'previous', $message->payload);  
            } else {
                Redis::hmset($redis_smember, 'previous', $new);
                Redis::hmset($redis_smember, 'latest', $message->payload);

            }
            
        } catch (Exception $e) {
            echo $e->getMessage();
        } 
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $groupname = env('KAFKA_MON_TOOL_GROUP_NAME', 'fe_kafka_monitoring_tool');

        $conf = new \RdKafka\Conf();
        $conf->set('group.id', $groupname);

        $rk = new \RdKafka\Consumer($conf);
        $rk->addBrokers(env('KAFKA_BROKERS'));

        $topicConf = new \RdKafka\TopicConf();
        $topicConf->set('auto.commit.interval.ms', 1000);
        $topicConf->set('enable.auto.commit', 'false');
        $topicConf->set('offset.store.method', 'broker');
        $topicConf->set('auto.offset.reset', 'latest');
        $queue = $rk->newQueue();
        $topic = $rk->newTopic(env('KAFKA_SCRAPE_MINMAX_ODDS'), $topicConf);
        $topic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);
     
        while (true) {
            $message=$queue->consume(1000);
             if ($message) {
                
                switch($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                            
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
}
