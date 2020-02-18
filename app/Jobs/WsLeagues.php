<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WsLeagues implements ShouldQueue
{
    use Dispatchable;

    protected $schedule = [
        'getEarlyLeagues'  => 'early',
        'getInPlayLeagues' => 'in play',
        'getTodayLeagues'  => 'today'
    ];

    public function __construct()
    {
    }


    public function handle()
    {
        //@TODO Transformation
//        wsEmit(['getLeagues' => ['test' => 1]]);
//
//        $server = app('swoole');
//        wsEmit($server->eventsTable[2]);

        $leagues = DB::table('leagues')->get();
        $a = wsEmit($leagues);
        Log::debug($a);
    }
}
