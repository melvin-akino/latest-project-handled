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
}
