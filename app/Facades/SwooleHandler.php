<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;


class SwooleHandler extends Facade
{
    protected static function getFacadeAccessor() { return 'swoolehandler'; }
}
