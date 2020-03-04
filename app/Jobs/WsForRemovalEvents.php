<?php

namespace App\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WsForRemovalEvents implements ShouldQueue
{
    use Dispatchable;

    public function __construct($data)
    {
        $this->data             = $data;
    }

    public function handle()
    {
        wsEmit(['getForRemovalEvents' => $this->data]);
    }
}
