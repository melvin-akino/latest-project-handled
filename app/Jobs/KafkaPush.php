<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Handlers\ProducerHandler;

class KafkaPush implements ShouldQueue
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @param string $kafkaTopic
     * @param array  $message
     * @param string $key
     * @return void
     */
    public function __construct($kafkaTopic, $message, $key)
    {
        $this->kafkaTopic = $kafkaTopic;
        $this->message    = $message;
        $this->key        = $key;
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

        Log::info('Sending to Kafka ' . $this->kafkaTopic);
        $producerHandler->setTopic($this->kafkaTopic)->send($this->message, $this->key);
        Log::channel('kafkaproducelog')->info(json_encode($this->message));
    }
}
