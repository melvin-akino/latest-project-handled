<?php

namespace App\Listeners;

use App\Events\ProcessedOdds;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use SwooleTW\Http\Websocket\Facades\Websocket;

class WebSocketSubscriber implements ShouldQueue
{

    use InteractsWithQueue;

    /**
     * @param $events
     */
    public function handleLeagues($events)
    {
        $websocket = new Websocket();
        var_dump($websocket::broadcast()->emit('changeOdds', ['test1']));
    }

    /**
     * @param $events
     */
    public function handleOdds($events)
    {
        $websocket = new Websocket();
        for ($i = 0; $i < 1000000; $i++) {
            //
        }
        var_dump($websocket::broadcast()->emit('changeOdds', ['test2']));
    }

    /**
     * @param $events
     */
    public function handleWatchList($events)
    {
        $websocket = new Websocket();
        var_dump($websocket::broadcast()->emit('changeOdds', ['test3']));
    }

    public function failed(ProcessedOdds $processedOdds, $exception)
    {
        //
    }

    public function subscribe($events)
    {
        $events->listen(
            'App\Events\ProcessedOdds',
            'App\Listeners\WebSocketSubscriber@handleLeagues'
        );

        $events->listen(
            'App\Events\ProcessedOdds',
            'App\Listeners\WebSocketSubscriber@handleOdds'
        );

        $events->listen(
            'App\Events\ProcessedOdds',
            'App\Listeners\WebSocketSubscriber@handleWatchList'
        );
    }
}
