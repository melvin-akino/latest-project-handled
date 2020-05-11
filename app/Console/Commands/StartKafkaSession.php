<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PrometheusMatric;
use Exception;
 
 
class StartKafkaSession extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'startkafka:session';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will start a Kafka debug session';

    protected $topicList = [
                "hg_req",
                "hg_bet_req",
                "hg_minmax_req",
                "hg_openorder_req",
                "hg_settlement_req",
                "hg_balance_req",
                "QM"
            ];    
            
    protected $topicObj = [];
    protected $promData = [];

    protected $stats = [
            "totalMessages"     => 0,
            "wrongTopic"        => 0,
            "unknownSubCommand" => 0,
            "minmax"            => [],
            ];


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function refresh() 
    {
               
        echo "Total Messages in Session: " . $this->stats["totalMessages"] . "\n\r\n\r";
        echo "Errors:\r\n";
        echo "\twrongTopic: ".$this->stats["wrongTopic"]."\n\r";
        echo "\tunknownSubCommand: " . $this->stats["unknownSubCommand"] . "\n\r";
        echo "\nMINMAX:\n\r";

        foreach($this->stats["minmax"] as $k => $v) 
        {
            if ($v["req"] != 0) 
            {
                if (array_key_exists($k, $this->promData))
                {
                    if ($this->promData[$k]['req'] != $v["req"]) PrometheusMatric::MakeMatrix('betslip_kafkamon_request_id', 'Market Id Request.', $k);
                        
                    if ($this->promData[$k]['rep'] != $v["rep"]) PrometheusMatric::MakeMatrix('betslip_kafkamon_reply_id', 'Market Id reply.', $k);
                       
                    $this->promData[$k] = ['req' => $v["req"], 'rep' =>$v["rep"]];

                } else {
                    PrometheusMatric::MakeMatrix('betslip_kafkamon_request_id', 'Market Id Request.',$k);
                    PrometheusMatric::MakeMatrix('betslip_kafkamon_reply_id', 'Market Id reply.',$k);
                    $this->promData[$k] = ['req' => $v["req"], 'rep' => $v["rep"]];
                }
            }
                echo "\t" . $k . "\t\tREQ: " . $v["req"] . "\tREPLY: " . $v["rep"]."\n\r";
        }
    }

    public function handleMessage($message)
    {
        $this->stats["totalMessages"]++;

        $payload=json_decode($message->payload);
        switch($payload->command) 
        {
            case "minmax":
                if($payload->sub_command == "scrape") 
                {
                    if($message->topic_name == "hg_minmax_req") 
                    {
                        if(array_key_exists($payload->data->market_id,$this->stats["minmax"])) 
                        {
                            $this->stats["minmax"][$payload->data->market_id]["req"]++;
                        }
                        else {
                            $this->stats["minmax"][$payload->data->market_id] = [
                                    "req" => 1,
                                    "rep" => 0,
                                    ];
                        }
                    }
                    else {
                        $this->stats["wrongTopic"]++;
                    }
                        
                }
                elseif($payload->sub_command == "transform")
                {
                    if($message->topic_name == "MINMAX-ODDS") 
                    {
                        if(array_key_exists($payload->data->market_id,$this->stats["minmax"])) 
                        {
                            $this->stats["minmax"][$payload->data->market_id]["rep"]++;
                        }
                        else {
                            $this->stats["minmax"][$payload->data->market_id] = [
                                    "req" => 0,
                                    "rep" => 1,
                                ];
                        }

                    }
                    else {
                        $this->stats["wrongTopic"]++;
                    }
                }
                else {
                    $this->stats["unknownSubCommand"]++;
                }
                break;
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $declaredTopicEnv = [env('KAFKA_SCRAPE_ODDS', 'SCRAPING-ODDS'), env('KAFKA_SCRAPE_LEAGUES', 'SCRAPING-PROVIDER-LEAGUES'), 
                    env('KAFKA_SCRAPE_EVENTS', 'SCRAPING-PROVIDER-EVENTS'), env('AFKA_SCRAPE_MINMAX_ODDS', 'MINMAX-ODDS'), 
                    env('KAFKA_BET_PLACED', 'PLACED-BET'),env('KAFKA_OPEN_ORDERS', 'OPEN-ORDERS'), 
                    env('KAFKA_SCRAPE_SETTLEMENT', 'SCRAPING-SETTLEMENTS'), env('KAFKA_SCRAPE_BALANCE', 'BALANCE')];

        $conf = new \RdKafka\Conf();
        $conf->set('group.id', 'multiline_KafkaMon');

        $rk = new \RdKafka\Consumer($conf);
        $rk->addBrokers(env('KAFKA_BROKERS'));

        $topicConf = new \RdKafka\TopicConf();
        $topicConf->set('auto.commit.interval.ms', 100);
        $topicConf->set('offset.store.method', 'broker');

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'smallest': start from the beginning
        $topicConf->set('auto.offset.reset', 'latest');

        for ($x = 0 ;$x < count($declaredTopicEnv); $x++) {
            array_push($this->topicList, $declaredTopicEnv[$x]);
        }

        $queue = $rk->newQueue();
        $topic = NULL;
        foreach($this->topicList as $t) {
            $low  = 0;
            $high = 0;
            $rk->queryWatermarkOffsets($t, 0, $low, $high, 1000);

            $topic = $rk->newTopic($t, $topicConf);
            // Start consuming partition 0
            $topic->consumeQueueStart(0, RD_KAFKA_OFFSET_END, $queue);

            $this->topicObj[$t]=$topic;

            $topic = NULL;
        }
        $currTime = time();
        while(true) 
        {
            if(time()-$currTime > 1) 
            {
                $currTIme = time();
                $this->refresh();
            }
            $message=$queue->consume(120 * 10000);
            switch($message->err) 
            {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                      
                    $this->handleMessage($message);
                    $this->topicObj[$message->topic_name]->offsetStore($message->partition, $message->offset);
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
