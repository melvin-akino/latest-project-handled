<?php

namespace App\Tasks;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use App\Handlers\OddsTransformationHandler;
use Exception;

class TransformKafkaMessageOdds extends Task
{
    protected $message;
    protected $internalParameters;
    protected $oddsTransformationHandler;

    public function __construct($message, $internalParameters, $oddsTransformationHandler)
    {
        $this->message                   = $message;
        $this->internalParameters        = $internalParameters;
        $this->oddsTransformationHandler = $oddsTransformationHandler;
    }

    public function handle()
    {
        $oddsTransformationHandler = $this->oddsTransformationHandler->init($this->message, $this->internalParameters)->handle();
    }
}
