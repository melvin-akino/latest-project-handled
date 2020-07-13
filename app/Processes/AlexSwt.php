<?php

namespace App\Processes;

use App\Facades\SwooleHandler;
use Illuminate\Support\Str;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Swoole\Http\Server;
use Swoole\Process;
use PrometheusMatric;

class BalanceProduce implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;
    private static $producerHandler;

    public static function callback(Server $swoole, Process $process)
    {
        //wsTable
        for ($i = 0; $i < env('SWT_MAX_SIZE'); $i++) {
            SwooleHandler::set('wsTable', uniqid(), ['value' => Str::random(10000)]);
        }

        //data2SwtTable
        for ($i = 0; $i < 5; $i++) {
            SwooleHandler::set('data2SwtTable', uniqid(), ['value' => 1]);
        }

        //priorityTriggerTable
        for ($i = 0; $i < 5; $i++) {
            SwooleHandler::set('priorityTriggerTable', uniqid(), ['value' => 1]);
        }

        //rawLeaguesTable
        for ($i = 0; $i < 10000; $i++) {
            SwooleHandler::set('rawLeaguesTable', uniqid(), [
                'id' => $i,
                'sport_id' => $i,
                'provider_id' => $i,
                'name' => Str::random(10000)
            ]);
        }

        //rawTeamsTable
        for ($i = 0; $i < 20000; $i++) {
            SwooleHandler::set('rawTeamsTable', uniqid(), [
                'id' => $i,
                'sport_id' => $i,
                'provider_id' => $i,
                'name' => Str::random(10000)
            ]);
        }

        //rawEventsTable
        for ($i = 0; $i < env('SWT_MAX_SIZE'); $i++) {
            SwooleHandler::set('rawEventsTable', uniqid(), [
                'id' => $i,
                'event_identifier' => $i,
                'provider_id' => $i
            ]);
        }

    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
