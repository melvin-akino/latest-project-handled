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

    protected $config;
    protected $variableConfig;
    protected $selectConfig;
    protected $systemConfiguration;

    const SCHEDULE_INPLAY_TIMER = 'SCHEDULE_INPLAY_TIMER';
    const INTERVAL_REQ_PER_EXEC_INPLAY = 'INTERVAL_REQ_PER_EXEC_INPLAY';
    const NUM_OF_REQ_PER_EXECUTION_INPLAY = 'NUM_OF_REQ_PER_EXECUTION_INPLAY';
    const SCHEDULE_TODAY_TIMER = 'SCHEDULE_TODAY_TIMER';
    const INTERVAL_REQ_PER_EXEC_TODAY = 'INTERVAL_REQ_PER_EXEC_TODAY';
    const NUM_OF_REQ_PER_EXECUTION_TODAY = 'NUM_OF_REQ_PER_EXECUTION_TODAY';
    const SCHEDULE_EARLY_TIMER = 'SCHEDULE_EARLY_TIMER';
    const INTERVAL_REQ_PER_EXEC_EARLY = 'INTERVAL_REQ_PER_EXEC_EARLY';
    const NUM_OF_REQ_PER_EXECUTION_EARLY = 'NUM_OF_REQ_PER_EXECUTION_EARLY';

    const DB_CHECK_INTERVAL = 60 * 10;

    public function __construct()
    {
        parent::__construct();

        $kafka = new Producer(kafkaConfig());
        $this->topic = $kafka->newTopic(env('KAFKA_SCRAPE_REQUEST', 'scrape_req'));
        $this->providers = DB::connection(config('database.crm_default'))->table('providers')->where('is_enabled',
            true)->get()->toArray();
        $this->sports = DB::table('sports')->where('is_enabled', true)->get()->toArray();

        $this->selectConfig = [
            'inplay' => [
                self::SCHEDULE_INPLAY_TIMER,
                self::INTERVAL_REQ_PER_EXEC_INPLAY,
                self::NUM_OF_REQ_PER_EXECUTION_INPLAY,
            ],
            'today'  => [
                self::SCHEDULE_TODAY_TIMER,
                self::INTERVAL_REQ_PER_EXEC_TODAY,
                self::NUM_OF_REQ_PER_EXECUTION_TODAY,
            ],
            'early'  => [
                self::SCHEDULE_EARLY_TIMER,
                self::INTERVAL_REQ_PER_EXEC_EARLY,
                self::NUM_OF_REQ_PER_EXECUTION_EARLY
            ]
        ];
        $this->systemConfiguration = new SystemConfiguration();

        $this->config();

        $this->variableConfig = [
            self::SCHEDULE_INPLAY_TIMER           => 'timer',
            self::INTERVAL_REQ_PER_EXEC_INPLAY    => 'requestInterval',
            self::NUM_OF_REQ_PER_EXECUTION_INPLAY => 'requestNumber',
            self::SCHEDULE_TODAY_TIMER            => 'timer',
            self::INTERVAL_REQ_PER_EXEC_TODAY     => 'requestInterval',
            self::NUM_OF_REQ_PER_EXECUTION_TODAY  => 'requestNumber',
            self::SCHEDULE_EARLY_TIMER            => 'timer',
            self::INTERVAL_REQ_PER_EXEC_EARLY     => 'requestInterval',
            self::NUM_OF_REQ_PER_EXECUTION_EARLY  => 'requestNumber',
        ];
    }

    public function handle()
    {
        $i = 0;
        while (true) {
            if ($i % self::DB_CHECK_INTERVAL == 0) {
                $this->config();
            }

            $request = [];
            foreach ($this->selectConfig as $key => $scheduleType) {
                foreach ($this->config as $conf) {
                    if (in_array($conf['type'], $scheduleType)) {
                        $request[$key][$this->variableConfig[$conf['type']]] = $conf['value'];
                    }
                }
            }

            foreach ($request as $key => $req) {
                $this->scheduleType = $key;
                if ($i % $req['timer'] == 0) {
                    for ($interval = 0; $interval < $req['requestNumber']; $interval++) {
                        $this->scrapeRequest();
                        usleep($req['requestInterval'] * 1000);
                    }
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

    private function config()
    {
        $this->systemConfiguration->where(true, true);
        foreach ($this->selectConfig as $scheduleType) {
            foreach ($scheduleType as $where) {
                $this->systemConfiguration->orWhere('type', $where);
            }
        }
        $this->config = $this->systemConfiguration->select('type', 'value')->get()->toArray();
    }
}
