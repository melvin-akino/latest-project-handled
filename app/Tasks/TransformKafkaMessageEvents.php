<?php

namespace App\Tasks;

use App\Jobs\WsForRemovalEvents;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Exception;
use Illuminate\Support\Facades\Log;

class TransformKafkaMessageEvents extends Task
{
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

            if ($eventScrapingTable->exists($timestampSwtId)) {
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

            if ($providersTable->exist($providerSwtId)) {
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

            if ($sportsTable->exists($sportSwtId)) {
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
            if ($activeEventsTable->exists($activeEventsSwtId)) {
                $eventsJson = $activeEventsTable->get($activeEventsSwtId);
                $events     = json_decode($eventsJson['events'], true);

                $inActiveEvents = array_diff($events, $this->message->data->event_ids);

                foreach ($inActiveEvents as $eventId) {
                    if ($eventsTable->exists("sId:$sportId:pId:$providerId:eventIdentifier:$eventId")) {
                        $eventsTable->del("sId:$sportId:pId:$providerId:eventIdentifier:$eventId");
                    }
                }
                Log::debug($this->message->data->schedule);
                Log::debug(json_encode(['active-events' => $events]));
                Log::debug(json_encode(['payload-events' => $this->message->data->event_ids]));
                Log::debug(json_encode(['for-removal-events' => $inActiveEvents]));
                WsForRemovalEvents::dispatch($inActiveEvents);
            }

            $activeEventsTable->set($activeEventsSwtId, ['events' => json_encode($this->message->data->event_ids)]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
