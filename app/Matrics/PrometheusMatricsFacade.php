<?php

namespace App\Matrics;

use Illuminate\Support\Facades\Facade;

class PrometheusMatricsFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'PrometheusMatric';
    }
}

?>