<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class WalletFacade extends Facade
{
    protected static function getFacadeAccessor() { return 'App\Services\WalletService'; }
}
