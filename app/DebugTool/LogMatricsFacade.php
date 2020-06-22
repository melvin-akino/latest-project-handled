<?php

namespace App\DebugTool;

use Illuminate\Support\Facades\Facade;

class LogMatricsFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'LogMatrics';
    }
}

?>