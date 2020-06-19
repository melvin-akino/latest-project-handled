<?php

namespace App\Tasks;

use Hhxsv5\LaravelS\Swoole\Task\Task;

class TransformKafkaMessageOdds extends Task
{
    protected $message;
    protected $internalParameters;

    public function init($message, $internalParameters)
    {
        $this->message            = $message;
        $this->internalParameters = $internalParameters;
        return $this;
    }

    public function handle()
    {
        $oddsTransformationHandler = resolve('OddsTransformationHandler');
        $oddsTransformationHandler->init($this->message, $this->internalParameters)->handle();
    }
}
