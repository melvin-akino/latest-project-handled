<?php

namespace App\Tasks;

use App\Models\EventMarket;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class TransformationEventMarketUpdate extends Task
{
    public function __construct($marketId, $odds)
    {
        $this->marketId = $marketId;
        $this->odds = $odds;
    }

    public function handle()
    {
        EventMarket::updateOrCreate([
            'bet_identifier' => $this->marketId
        ], [
            'odds' => $this->odds
        ]);
    }
}
