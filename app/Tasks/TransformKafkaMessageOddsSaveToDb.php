<?php

namespace App\Tasks;

use Hhxsv5\LaravelS\Swoole\Task\Task;

class TransformKafkaMessageOddsSaveToDb extends Task
{
    protected $subTasks;
    protected $uid;
    protected $dbOptions;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function init(array $subTasks = [], string $uid = null, array $dbOptions)
    {
        $this->subTasks  = $subTasks;
        $this->uid       = $uid;
        $this->dbOptions = $dbOptions;

        return $this;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $oddsSaveToDbHandler = resolve('OddsSaveToDbHandler');
        $oddsSaveToDbHandler->init($this->subTasks, $this->uid, $this->dbOptions)->handle();
    }
}
