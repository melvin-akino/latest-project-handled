<?php

namespace App\Jobs;

use App\Models\EventMarket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class TransformationEventMarketUpdate implements ShouldQueue
{
    use Dispatchable;

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
