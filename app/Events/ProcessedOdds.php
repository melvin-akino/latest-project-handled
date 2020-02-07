<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RdKafka\Message;

class ProcessedOdds
{
    use SerializesModels;

    public $message;

    /**
     * ProcessedOdds constructor.
     * @param Message $message
     */
    public function __construct($message)
    {
        Log::info(json_encode($message));
        $this->message = $message;
    }
}
