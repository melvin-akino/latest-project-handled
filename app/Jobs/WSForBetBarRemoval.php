<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WSForBetBarRemoval implements ShouldQueue
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fd, $orderId)
    {
        $this->fd      = $fd;
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $server = app('swoole');

        if ($server->isEstablished($this->fd)) {
            $server->push($this->fd, json_encode([
                'forBetBarRemoval' => ['order_id' => $this->orderId]
            ]));
        }
    }
}
