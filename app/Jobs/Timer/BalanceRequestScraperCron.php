<?php

namespace App\Jobs\Timer;

use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\Str;
use App\Handlers\ProducerHandler;
use App\Jobs\KafkaPush;
use App\Models\SystemConfiguration;

class BalanceRequestScraperCron extends CronJob
{
	protected $type = 'SCRAPER';
	protected $i = 1;
    protected $systemConfigurationsTimers = [];

    // !!! The `interval` and `isImmediate` of cron job can be configured in two ways(pick one of two): one is to overload the corresponding method, and the other is to pass parameters when registering cron job.
    // --- Override the corresponding method to return the configuration: begin
    public function interval()
    {
        return 1000;// Run every 1000ms
    }
    public function isImmediate()
    {
        return false;// Whether to trigger `run` immediately after setting up
    }
    // --- Override the corresponding method to return the configuration: end
    public function run()
    {
    	$this->i++;	

        $refreshDBInterval = config('balance.refresh-db-interval');

        if ($this->i % $refreshDBInterval == 0) {
            $this->systemConfigurationsTimers = $this->refreshDbConfig();
        }

        if (!empty($this->systemConfigurationsTimers)) {
            foreach ($this->systemConfigurationsTimers as $systemConfigurationsTimer) {
                if ($this->i % (int) $systemConfigurationsTimer['value'] == 0) {
                    $this->sendPayload($systemConfigurationsTimer['type']);
                }
            }
        }
    }

    private function milliseconds()
    {
        $mt = explode(' ', microtime());
        return bcadd($mt[1], $mt[0], 8);
    }

    private function refreshDbConfig()
    {
        return SystemConfiguration::whereIn('type', ['BET_VIP', 'BET_NORMAL'])->get()->toArray();
    }

    private function sendPayload($type)
    {
        $kafkaTopic = env('KAFKA_SCRAPE_BALANCE_POSTFIX', '_balance_req');

        $kafkaProducer   = app('KafkaProducer');
        $producerHandler = new ProducerHandler($kafkaProducer);

        $providerAccount = DB::table('provider_accounts as pa')
                            ->join('providers as p', 'p.id', 'pa.provider_id')
                            ->where('p.is_enabled', true)
                            ->where('type', $type)
                            ->whereNull('deleted_at')
                            ->select('username', 'alias')
                            ->first();

        $username = $providerAccount->username;
        $provider = strtolower($providerAccount->alias);

        $requestId = (string) Str::uuid();
        $requestTs = $this->milliseconds();

        $payload = [
            'request_uid' => $requestId,
            'request_ts'  => $requestTs,
            'sub_command' => 'scrape',
            'command'     => 'balance'
        ];
        $payload['data'] = [
            'provider'  => $provider,
            'username'  => $username
        ];

        KafkaPush::dispatch($provider . $kafkaTopic, $payload, $requestId);
    }
}