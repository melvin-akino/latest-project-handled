<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DateTime;
use Exception;

class TransformKafkaMessageLeagues implements ShouldQueue
{
    use Dispatchable;

    protected $message;
    protected $swoole;

    public function __construct($message)
    {
        $this->message = json_decode($message->payload);
    }

    public function handle()
    {
        $swoole  = app('swoole');
        $wsTable = $swoole->wsTable;

        if (empty($this->message->data)) {
            return;
        }
}
