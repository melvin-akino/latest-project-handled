<?php

namespace App\Jobs\Timer;

use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Jobs\KafkaPush;

class MinMaxRequestScraperCron extends CronJob
{
    // !!! The `interval` and `isImmediate` of cron job can be configured in two ways(pick one of two): one is to overload the corresponding method, and the other is to pass parameters when registering cron job.
    // --- Override the corresponding method to return the configuration: begin
    public function interval()
    {
        return 1000;// Run every 1000ms
    }

    public function isImmediate()
    {
        return true;// Whether to trigger `run` immediately after setting up
    }

    // --- Override the corresponding method to return the configuration: end
    public function run()
    {
        $minMaxRequestsTable = app('swoole')->minMaxRequestsTable;

        foreach ($minMaxRequestsTable as $minMaxRequest) {
            $requestId = Str::uuid();
            $requestTs = self::milliseconds();

            $payload = [
                'request_uid' => $requestId,
                'request_ts'  => $requestTs,
                'sub_command' => 'scrape',
                'command'     => 'minmax'
            ];
            $payload['data'] = $minMaxRequest;
            $kafkaTopic = env('KAFKA_SCRAPE_MINMAX_REQUEST_POSTFIX', '_minmax_req');
            KafkaPush::dispatch(strtolower($minMaxRequest['provider']) . $kafkaTopic, $payload, $requestId);
        }
    }

    private function milliseconds()
    {
        $mt = explode(' ', microtime());
        return bcadd($mt[1], $mt[0], 8);
    }

    private function refreshDbConfig()
    {
        return DB::table('provider_accounts as pa')
            ->join('providers as p', 'p.id', 'pa.provider_id')
            ->where('p.is_enabled', true)
            ->whereIn('type', ['BET_VIP', 'BET_NORMAL'])
            ->whereNull('deleted_at')
            ->select('username', 'alias', 'pa.provider_id')
            ->get()
            ->toArray();
    }
}
