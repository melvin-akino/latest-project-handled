<?php

namespace App\Processes;

use App\Models\Sport;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Swoole\Http\Server;
use Swoole\Process;

class Data2SWT implements CustomProcessInterface
{
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {

        while (!self::$quit) {
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }


}
