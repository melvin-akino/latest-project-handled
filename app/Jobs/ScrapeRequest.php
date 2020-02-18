<?php

namespace App\Jobs;

use App\Events\ScrapeRequestEvent;
use App\Tasks\ScrapeRequestJob;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\DB;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use App\Tasks\ScrapeRequestTask;
use RdKafka\{Conf as KafkaConf, Producer};
use Hhxsv5\LaravelS\Swoole\Task\Event;

abstract class ScrapeRequest extends CronJob
{
    protected $providers;
    protected $scheduleType;

    public function __construct()
    {
        parent::__construct();
        $this->providers = DB::connection(config('database.crm_default'))->table('providers')->where('is_enabled',
            true)->get()->toArray();
    }

    public function interval()
    {
        return 1000;
    }

    public function isImmediate()
    {
        return true;
    }

    public function run()
    {
        foreach ($this->providers as $provider) {
            ScrapeRequestJob::dispatch($this->scheduleType, $provider);
        }
    }
}
