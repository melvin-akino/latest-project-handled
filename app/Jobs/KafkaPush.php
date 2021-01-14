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
        $producerHandler = app('ProducerHandler');
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
            $toLogs = [
                "class"       => "KafkaPush",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "JOB_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_jobs', 'critical', $toLogs);
        } finally {
            if (env('CONSUMER_PRODUCER_LOG', false)) {
                Log::channel('kafkaproducelog')->info(json_encode($this->message));

                $toLogs = [
                    "class"       => "KafkaPush",
                    "message"     => $this->message,
                    "module"      => "JOB",
                    "status_code" => 200,
                ];
                monitorLog('monitor_jobs', 'info', $toLogs);
            }
        }
    }
}
