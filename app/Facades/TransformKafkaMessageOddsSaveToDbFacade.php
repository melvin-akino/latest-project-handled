<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;


class TransformKafkaMessageOddsSaveToDbFacade extends Facade
{
    protected static function getFacadeAccessor() { return 'TransformKafkaMessageOddsSaveToDb'; }

}