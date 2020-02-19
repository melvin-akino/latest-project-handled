<?php

namespace App\Console\Commands;

use App\Models\SystemConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RdKafka\Conf as KafkaConf;
use RdKafka\Producer;

class ScraperRequest extends Command
{

    protected $signature = 'scraper:request';

    protected $description = 'Scraper Request';

    private $schedule = [
        'inplay', 'today', 'early'
    ];

    public function __construct()
    {
        parent::__construct();

        $kafka = new Producer(kafkaConfig());
        $this->topic = $kafka->newTopic(env('KAFKA_SCRAPE_REQUEST', 'scrape_req'));
        $this->providers = DB::connection(config('database.crm_default'))->table('providers')->where('is_enabled',
            true)->get()->toArray();
        $this->sports = DB::table('sports')->where('is_enabled', true)->get()->toArray();
    }

    public function handle()
    {
        $systemConfiguration = new SystemConfiguration();

        $i = 1;
        while (true) {
            //INPLAY
            $timer = $systemConfiguration->where('type', 'SCHEDULE_INPLAY_TIMER')->first();
            $requestInterval = $systemConfiguration->where('type', 'INTERVAL_REQ_PER_EXEC_INPLAY')->first();
            $requestNumber = $systemConfiguration->where('type', 'NUM_OF_REQ_PER_EXECUTION_INPLAY')->first();

            if ($i % (int) $timer->value  == 0) {
                $this->scheduleType = 'inplay';
                for ($interval = 0; $interval < $requestNumber->value; $interval++) {
                    $this->scrapeRequest();
                    usleep($requestInterval->value * 1000);
                }
            }

            //TODAY
            $timer = $systemConfiguration->where('type', 'SCHEDULE_TODAY_TIMER')->first();
            $requestInterval = $systemConfiguration->where('type', 'INTERVAL_REQ_PER_EXEC_TODAY')->first();
            $requestNumber = $systemConfiguration->where('type', 'NUM_OF_REQ_PER_EXECUTION_TODAY')->first();
            if ($i % (int) $timer->value  == 0) {
                $this->scheduleType = 'today';
                for ($interval = 0; $interval < $requestNumber->value; $interval++) {
                    $this->scrapeRequest();
                    usleep($requestInterval->value * 1000);
                }
            }

            //EARLY
            $timer = $systemConfiguration->where('type', 'SCHEDULE_EARLY_TIMER')->first();
            $requestInterval = $systemConfiguration->where('type', 'INTERVAL_REQ_PER_EXEC_EARLY')->first();
            $requestNumber = $systemConfiguration->where('type', 'NUM_OF_REQ_PER_EXECUTION_EARLY')->first();
            if ($i % (int) $timer->value  == 0) {
                $this->scheduleType = 'early';
                for ($interval = 0; $interval < $requestNumber->value; $interval++) {
                    $this->scrapeRequest();
                    usleep($requestInterval->value * 1000);
                }
            }

            $i++;
            sleep(1);
        }
    }

    private function scrapeRequest()
    {
        foreach ($this->providers as $provider) {
            foreach ($this->sports as $sport) {
                $prePayload = [
                    'request_uid' => uniqid(),
                    'request_ts'  => $this->milliseconds(),
                    'command'     => 'odd',
                    'sub_command' => 'scrape',
                ];
                $prePayload['data'] = [
                    'provider' => strtolower($provider->alias),
                    'schedule' => $this->scheduleType,
                    'sport'    => $sport->id
                ];

                $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($prePayload));
            }
        }
    }

    private function milliseconds()
    {
        $mt = explode(' ', microtime());
        return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
    }
}
