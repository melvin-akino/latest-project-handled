<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class LeagueFacade extends Facade
{
    protected static function getFacadeAccessor() { return 'App\Services\LeagueService'; }
}
