<?php

namespace App\Tasks;

use App\Models\EventsData;
use Hhxsv5\LaravelS\Swoole\Task\Task;

class UpdateMatchedEventData extends Task
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle()
    {
        $swoole = app('swoole');

        $doesExist = false;
        foreach ($swoole->eventsTable as $key => $event) {
            if ($event['event_identifier'] == $this->message->data->events[0]->eventId) {
                $doesExist = true;
                break;
            }
        }

        if ($doesExist) {
            $event = EventsData::where('event_identifier', $this->message->data->events[0]->eventId)->first();
            if ($event) {
                $event->is_matched = true;
                $event->save();
            }
        }
    }
}
