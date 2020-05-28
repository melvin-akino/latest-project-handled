<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Exception;

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
        try {
            
            $payload            = json_decode($message->payload);       
            $leagueName         = $payload->data->leagueName;
            $homeTeam           = $payload->data->homeTeam;
            $awayTeam           = $payload->data->awayTeam;
            $provider           = $payload->data->provider;
            $schedule           = $payload->data->schedule;
            $redisTopic         = env('REDIS_TOOL_SCRAPE_ODDS', 'REDIS-MON-TOOL-SCRAPING-ODDS');
            $redisExpiration    = env('REDIS_TOOL_SCRAPE_EXPIRE', 300);
            $sport              = $payload->data->sport;
            $redis_smember      = $leagueName .'-' .$homeTeam .'-'. $awayTeam .'-'. $provider .'-'. $sport .'-'. $schedule;
            $redis_smember      = str_replace(" ","",$redis_smember);

            $gameExist = DB::table('master_events as me')
                        ->join('master_leagues as ml', 'ml.id', 'me.master_league_id')
                        ->join('master_teams as ht', 'ht.id', 'me.master_team_home_id')
                        ->join('master_teams as at', 'at.id', 'me.master_team_away_id')
                        ->where('ml.name', $leagueName)
                        ->where('ht.name', $homeTeam)
                        ->where('at.name', $awayTeam)
                        ->where('game_schedule', $schedule)
                        ->select(['ml.name as league_name',
                                'ht.name as home_team_name',
                                'at.name as away_team_name',
                                'game_schedule', 'me.deleted_at'])
                        ->first();
           
            if ($gameExist)
            {

                $gameDeleted = $gameExist->deleted_at;

                if ($gameDeleted){
                    Redis::srem($redisTopic,$redis_smember);

                } else {

                    $ttl = Redis::ttl($redisTopic);
                    if ($ttl < 0) Redis::expire($redisTopic, $redisExpiration);
                    
                    $members = Redis::sadd($redisTopic,$redis_smember);
                    $old = Redis::hget($redis_smember, 'previous');
                    $new =Redis::hget($redis_smember, 'latest');

                    if ($old ==false){

                        Redis::hmset($redis_smember, 'latest', $message->payload);
                        Redis::hmset($redis_smember, 'previous', $message->payload);

                    } else {

                        Redis::hmset($redis_smember, 'previous', $new);
                        Redis::hmset($redis_smember, 'latest', $message->payload);

                    }
                }
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
        $topicConf->set('auto.commit.interval.ms', 100);
        $topicConf->set('enable.auto.commit', 'false');
        $topicConf->set('offset.store.method', 'broker');
        $topicConf->set('auto.offset.reset', 'latest');
        $queue = $rk->newQueue();
        $topic = $rk->newTopic(env('KAFKA_SCRAPE_ODDS'), $topicConf);
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
