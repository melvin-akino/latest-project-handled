<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WsAdditionalLeagues implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId, $params)
    {
        $this->userId  = $userId;
        $this->sportId = $params[1];
    }

    public function handle()
    {
    }
}
