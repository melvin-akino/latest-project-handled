<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Exception;

class StartKafkaSettlement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'StartKafkaSettlement:session';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will start a Kafka settlement request debugging tool';

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
            $reqTs              = $payload->request_ts;
            $comName            = $payload->command;

            $redisTopic         = env('REDIS_TOOL_SETTLEMENT_REQUEST', 'REDIS-MON-TOOL-SETTLEMENT-REQUEST');
            $redisExpiration    = env('REDIS_TOOL_SETTLEMENT_REQUEST_EXPIRE', 300);

            $redis_smember      = $comName .'-' .$reqTs;

            $ttl = Redis::ttl($redisTopic);
            if ($ttl < 0) Redis::expire($redisTopic, $redisExpiration);
            
            $members = Redis::sadd($redisTopic,$redis_smember);

            Redis::hmset($redis_smember, $reqTs, $message->payload);

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
        $topicConf->set('auto.commit.interval.ms', 100);
        $topicConf->set('enable.auto.commit', 'false');
        $topicConf->set('offset.store.method', 'broker');
        $topicConf->set('auto.offset.reset', 'latest');
        $queue = $rk->newQueue();
        $topic = $rk->newTopic(env('KAFKA_SCRAPE_SETTLEMENTS'), $topicConf);
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
