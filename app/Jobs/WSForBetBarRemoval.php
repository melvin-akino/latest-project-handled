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
    public function __construct($fd)
    {
        $this->fd      = $fd;
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
                'forBetBarRemoval' => ['status' => true]
            ]));
        }
    }
}
