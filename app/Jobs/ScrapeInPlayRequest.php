<?php

namespace App\Jobs;

class ScrapeInPlayRequest extends ScrapeRequest
{
    protected $scheduleType = 'inplay';

    public function interval()
    {
        return 1000;
    }

    public function isImmediate()
    {
        return true;
    }

    public function run()
    {
        for ($i = 0; $i < 10; $i++) {
            usleep(200 * 1000);
            foreach ($this->providers as $provider) {
                foreach ($this->sports as $sport) {
                    $prePayload = [
                        'request_uid' => uniqid(),
                        'request_ts'  => $this->milliseconds(),
                        'command'     => 'odd',
                        'sub_command' => 'scrape',
                    ];
                    $prePayload['data'] = [
                        'provider' => strtolower($provider->alias),
                        'schedule' => $this->scheduleType,
                        'sport'    => $sport->id
                    ];

                    $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($prePayload));
                }
            }
        }
    }
}
