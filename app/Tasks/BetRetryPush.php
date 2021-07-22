<?php

namespace App\Tasks;

use App\Facades\SwooleHandler;
use App\Jobs\KafkaPush;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;
use Swoole\Coroutine;

class BetRetryPush extends Task
{
    protected $bet;
    protected $payload;
    protected $requestUID;

    public function init($bet, $payload, $requestUID)
    {
        $this->bet        = $bet;
        $this->payload    = $payload;
        $this->requestUID = $requestUID;

        $toLogs = [
            "class"       => "BetRetryPush",
            "message"     => "Initiating...",
            "module"      => "TASK",
            "status_code" => 102,
        ];
        monitorLog('monitor_tasks', 'info', $toLogs);

        return $this;
    }

    public function handle()
    {
        Log::info("Starting Task: BetRetryPush");

        try {
            if (!empty($this->bet['retry_type_id'])) {
                usleep(2000000);
            }

            KafkaPush::dispatch(
                strtolower($this->bet['alias']) . env('KAFKA_SCRAPE_ORDER_REQUEST_POSTFIX', '_bet_req'),
                $this->payload,
                $this->requestUID
            );
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "BetRetryPush",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "TASK_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_tasks', 'error', $toLogs);
        }

        Log::info("Ending Task: BetRetryPush");
    }
}
