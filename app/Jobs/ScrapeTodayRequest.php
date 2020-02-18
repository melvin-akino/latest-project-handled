<?php

namespace App\Jobs;

class ScrapeTodayRequest extends ScrapeRequest
{
    protected $scheduleType = 'today';

    public function interval()
    {
        return 5000;
    }

    public function isImmediate()
    {
        return true;
    }
}
