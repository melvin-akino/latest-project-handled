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
        try {
            appLog('info', 'Sending to Kafka Topic: ' . $this->kafkaTopic);
            $producerHandler->setTopic($this->kafkaTopic)->send($this->message, $this->key);
            if (env('APP_ENV') != 'local') {
                for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
                    $result = $kafkaProducer->flush(10000);
                    if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                        break;
                    }
                }
            }
            appLog('info', 'Sent to Kafka Topic: ' . $this->kafkaTopic);
        } catch (Exception $e) {
            Log::critical('Sending Kafka Message Failed', [
                'error' => $e->getMessage(),
                'code'  => $e->getCode()
            ]);
        } finally {
            if (env('CONSUMER_PRODUCER_LOG')) {
                Log::channel('kafkaproducelog')->info(json_encode($this->message));
            }
        }
    }
}
