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

    public function __construct($message, $internalParameters)
    {
        $this->message            = $message;
        $this->internalParameters = $internalParameters;
    }

    public function handle()
    {
        $oddsTransformationHandler = new OddsTransformationHandler($this->message, $this->internalParameters);
        $oddsTransformationHandler->handle();
    }
}
