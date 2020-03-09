<?php

namespace App\Tasks;

use App\Models\EventMarket;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

class TransformationEventMarketUpdate extends Task
{
    public function __construct($oddsSwtId, $marketId, $odds)
    {
        $this->marketId = $marketId;
        $this->odds = $odds;
        $this->oddsSwtId = $oddsSwtId;
    }

    public function handle()
    {
        try {
            EventMarket::updateOrCreate([
                'bet_identifier' => $this->marketId
            ], [
                'odds' => $this->odds
            ]);
            app('swoole')->wsTable->set($this->oddsSwtId, [ 'value' => json_encode($this->odds) ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
