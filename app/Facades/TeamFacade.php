<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class TeamFacade extends Facade
{
    protected static function getFacadeAccessor() { return 'App\Services\TeamService'; }
}
