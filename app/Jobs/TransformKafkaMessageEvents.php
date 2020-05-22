<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Exception;
use Illuminate\Support\Facades\Log;

class TransformKafkaMessageEvents implements ShouldQueue
{
    use Dispatchable;

    protected $message;
    protected $swoole;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle()
    {
        try {
            $swoole             = app('swoole');
            $eventScrapingTable = $swoole->eventScrapingTable;

            $timestampSwtId = 'eventScraping:' . implode(':', [
                    'sport:' . $this->message->data->sport,
                    'provider:' . $this->message->data->provider,
                    'schedule:' . $this->message->data->schedule
                ]);

            $doesExist = false;
            foreach ($eventScrapingTable as $key => $value) {
                if ($key == $timestampSwtId) {
                    $doesExist = true;
                    break;
                }
            }
            if ($doesExist) {
                $swooleTS = $eventScrapingTable[$timestampSwtId]['value'];

                if ($swooleTS > $this->message->request_ts) {
                    Log::info("Event Transformation ignored - Old Timestamp");
                    return;
                }
            }

            $eventScrapingTable[$timestampSwtId]['value'] = $this->message->request_ts;

            /** LOOK-UP TABLES */
            $providersTable    = $swoole->providersTable;
            $activeEventsTable = $swoole->activeEventsTable;
            $sportsTable       = $swoole->sportsTable;
            $eventsTable       = $swoole->eventsTable;

            /**
             * PROVIDERS Swoole Table
             *
             * @ref config.laravels.providers
             *
             * @var $providersTable  swoole_table
             *      $providerSwtId   swoole_table_key    "providerAlias:<strtolower($provider)>"
             *      $providerId      swoole_table_value  int
             */
            $providerSwtId = "providerAlias:" . strtolower($this->message->data->provider);

            $doesExist = false;
            foreach ($providersTable as $key => $value) {
                if ($key == $providerSwtId) {
                    $doesExist = true;
                    break;
                }
            }

            if ($doesExist) {
                $providerId = $providersTable->get($providerSwtId)['id'];
            } else {
                Log::info("Event Transformation ignored - Provider doesn't exist");
                return;
            }

            /**
             * SPORTS Swoole Table
             *
             * @ref config.laravels.sports
             *
             * @var $sportsTable  swoole_table
             *      $sportSwtId   swoole_table_key    "sId:<$sportId>"
             *      $sportId      swoole_table_value  int
             */
            $sportSwtId = "sId:" . $this->message->data->sport;

            $doesExist = false;
            foreach ($sportsTable as $key => $value) {
                if ($key == $sportSwtId) {
                    $doesExist = true;
                    break;
                }
            }
            if ($doesExist) {
                $sportId = $sportsTable->get($sportSwtId)['id'];
            } else {
                Log::info("Event Transformation ignored - Sport doesn't exist");
                return;
            }

            $activeEventsSwtId = implode(':', [
                'sId:' . $sportId,
                'pId:' . $providerId,
                'schedule:' . $this->message->data->schedule
            ]);

            $doesExist = false;
            foeach ($activeEventsTable as $key => $value) {
                if ($key == $activeEventsSwtId) {
                    $doesExist = true;
                    break;
                }
            }

            if ($doesExist) {
                $eventsJson = $activeEventsTable->get($activeEventsSwtId);
                $events     = json_decode($eventsJson['events'], true);

                $inActiveEvents = array_diff($events, $this->message->data->event_ids);

                foreach ($inActiveEvents as $eventId) {
                    $eventTableKey = "sId:$sportId:pId:$providerId:eventIdentifier:$eventId";
                    $doesExist = false;
                    foreach ($eventsTable as $k => $v) {
                        if ($k == $eventTableKey) {
                            $doesExist = true;
                            break;
                        }
                    }
                    if ($doesExist) {
                        $eventsTable->del($eventTableKey);
                    }
                }
                WsForRemovalEvents::dispatch($inActiveEvents, $providerId);
            }

            $activeEventsTable->set($activeEventsSwtId, ['events' => json_encode($this->message->data->event_ids)]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
