<?php

namespace App\Listeners;

use App\Events\ProcessedOdds;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use SwooleTW\Http\Websocket\Facades\Websocket;

class WebSocketOddsNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ProcessedOdds $processedOdds)
    {
        Log::info(json_encode($processedOdds->message));

//        $websocket = new Websocket();
        Websocket::broadcast()->emit('changeOdds', (array) $processedOdds->message);
    }

    public function failed(ProcessedOdds $processedOdds, $exception)
    {
        //
    }
}
