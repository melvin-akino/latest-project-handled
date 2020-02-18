<?php

namespace App\Jobs;

use App\Tasks\ScrapeRequestJob;

class ScrapeInPlayRequest extends ScrapeRequest
{
    protected $scheduleType = 'inplay';
    protected $providers;

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
        for ($i = 0; $i < 5; $i++) {
            usleep(200 * 1000);
            foreach ($this->providers as $provider) {
                ScrapeRequestJob::dispatch($this->scheduleType, $provider);
            }
        }
    }
}
