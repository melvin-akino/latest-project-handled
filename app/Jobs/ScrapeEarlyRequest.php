<?php

namespace App\Jobs;

class ScrapeEarlyRequest extends ScrapeRequest
{
    protected $scheduleType = 'early';

    public function interval()
    {
        return 10000;
    }

    public function isImmediate()
    {
        return true;
    }
}
