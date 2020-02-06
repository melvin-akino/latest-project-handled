<?php

namespace App\Events;

use App\Models\SportOddType;
use Illuminate\Queue\SerializesModels;

class ProcessedOdds
{
    use SerializesModels;

    public $test;

    /**
     * ProcessedOdds constructor.
     * @param SportOddType $sportOddType
     */
    public function __construct(SportOddType $sportOddType)
    {
        $this->test = $sportOddType;
    }
}
