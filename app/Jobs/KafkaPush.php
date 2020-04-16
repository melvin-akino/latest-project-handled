<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Handlers\ProducerHandler;

class KafkaPush implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($kafkaTopic, $message, $key)
    {
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
        $kafkaProducer   = app('KafkaProducer');
        $producerHandler = new ProducerHandler($kafkaProducer);

        $producerHandler->setTopic($this->kafkaTopic)->send($this->message, $this->key);
        Log::channel('kafkalog')->info(json_encode($this->message));
    }
}
