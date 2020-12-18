<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Models\{Currency, ExchangeRate, SystemConfiguration, ProviderAccount};
use Exception;
use Mail;

class StartKafaBalanceMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'StartKafaBalanceMonitoring:session';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will start a Kafka balance monitoring tool and send email base from threshold';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function redisCheck($username, $provider, $currency, $balance)
    {
        $return          = true;
        $redisTopic      = env('REDIS_TOOL_BALANCE', 'REDIS-MON-TOOL-BALANCE');
        $redisExpiration = env('REDIS_TOOL_BALANCE_EXPIRE', 3600);

        $redisSmember = $username. '-' .$provider. '-' .$currency;
        $members  = Redis::sadd($redisTopic, $redisSmember);
        $ttl     = Redis::ttl($redisTopic);

        if ($ttl < 0) Redis::expire($redisTopic, $redisExpiration);
        $isRecord = Redis::hget($redisSmember, 'balance');

        if ($isRecord) $return = false;

        Redis::hmset($redisSmember, 'balance', $balance);
        $ttl  = Redis::ttl($redisSmember);
        if ($ttl < 0) Redis::expire($redisSmember, $redisExpiration);

        return $return;

    }

    public function message($message)
    {
        try {

            $threshold  = env('PROVIDER_THRESHOLD', 3000);
            $payload    = json_decode($message->payload);
            if (count((array)$payload->data)==0) return;

            $provider   = $payload->data->provider;
            $username   = $payload->data->username;
            $balance    = $payload->data->available_balance;
            $currency   = $payload->data->currency;

            $shouldEmail  = $this->redisCheck($username, $provider, $currency, $balance);
            $baseCurrency = Currency::where('code', 'CNY')->first();
            $currencyCode = $baseCurrency->code;

            if ((strtoupper($currency) != strtoupper($currencyCode))) {

                $providerCurrency = Currency::where('code', $currency)->first();
                if ($providerCurrency) {
                    $exchangeRate = ExchangeRate::where('from_currency_id', $baseCurrency->id)
                        ->where('to_currency_id', $providerCurrency->id)
                        ->first();
                    $balance = $balance * $exchangeRate->exchange_rate;

                }
            }
            $providerUser = ProviderAccount::where('username',$username)->where('is_enabled', true)->first();

            if ($providerUser) {
                $type = $providerUser->type;
                $thresholdType = $type =='BET_NORMAL' ? 'BET_NORMAL_THRESHOLD' : 'BET_VIP_THRESHOLD';
                $threshold = SystemConfiguration::where('type', $thresholdType)->first()->value;
            }


            if ( (!empty($provider)) && (!empty($username)) && ((float)$balance <= (float)$threshold )) {

                $data = ['provider'  => $provider,
                         'username'  => $username,
                         'balance'   => $balance,
                         'currency'  => $currency,
                         'threshold' => $threshold
                        ];

                if ($shouldEmail) {

                    $configuration = SystemConfiguration::where('type', 'PROVIDER_THRESHOLD_SEND_EMAIL_TO')->first();
                    $emails = $configuration->value;
                    $emails = explode(",",$emails);
                    Mail::send('mail.balance-provider-threshold', $data, function($message) use ($emails) {
                        $message->to($emails)->subject('Provider account in threshold');
                        }

                    );
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
        $topicConf->set('auto.commit.interval.ms', 1000);
        $topicConf->set('enable.auto.commit', 'false');
        $topicConf->set('offset.store.method', 'broker');
        $topicConf->set('auto.offset.reset', 'latest');
        $queue = $rk->newQueue();
        $topic = $rk->newTopic(env('KAFKA_SCRAPE_BALANCE'), $topicConf);
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
