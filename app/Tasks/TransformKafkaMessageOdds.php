<?php

namespace App\Tasks;

use Hhxsv5\LaravelS\Swoole\Task\Task;

class TransformKafkaMessageOdds extends Task
{
    protected $offset;
    protected $internalParameters;

    public function init($offset, $internalParameters)
    {
        $this->offset             = $offset;
        $this->internalParameters = $internalParameters;
        return $this;
    }

    public function handle()
    {
        $oddsTransformationHandler = resolve('OddsTransformationHandler');
        $oddsTransformationHandler->init($this->offset, $this->internalParameters)->handle();
    }
}
