<?php

namespace App\Listeners;

use App\Events\ProcessedOdds;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use SwooleTW\Http\Websocket\Facades\Websocket;

class SaveOddsToDb implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ProcessedOdds $processedOdds)
    {
        $data = (array) $processedOdds->message;
        //save to db;
    }

    public function failed(ProcessedOdds $processedOdds, $exception)
    {
        //
    }
}
