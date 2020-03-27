<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class KafkaPush implements ShouldQueue
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($producerHandler, $kafkaTopic, $message, $key)
    {
        $this->producerHandler = $producerHandler;
        $this->kafkaTopic = $kafkaTopic;
        $this->message = $message;
        $this->key = $key;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->producerHandler->setTopic($this->kafkaTopic)->send($this->message, $this->key);
    }
}
