<?php

namespace App\Tasks;

use App\Handlers\OddsSaveToDbHandler;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class TransformKafkaMessageOddsSaveToDb extends Task
{
    protected $oddsSaveToDbHandler;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $subTasks = [], string $uid = null, array $dbOptions)
    {
        $this->oddsSaveToDbHandler = new OddsSaveToDbHandler($subTasks, $uid, $dbOptions);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->oddsSaveToDbHandler->handle();
    }
}
