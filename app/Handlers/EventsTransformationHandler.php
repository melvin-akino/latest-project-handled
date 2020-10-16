<?php

namespace App\Handlers;

use App\Facades\SwooleHandler;
use App\Models\{SystemConfiguration};
use Exception;
use Illuminate\Support\Facades\{Log, DB, Redis};

class EventsTransformationHandler
{
    protected $message;
    protected $offset;
    protected $swoole;

    public function init($message, $offset, $swoole)
    {
        $this->message = $message;
        $this->offset  = $offset;
        $this->swoole  = $swoole;

        return $this;
    }

    public function handle()
    {
        try {
            $startTime = microtime(TRUE);

            if (env('APP_ENV') != "local") {
                if (!Redis::exists('type:events:requestUID:' . $this->message->request_uid)) {
                    appLog('info', "Events Transformation ignored - Request UID is not from ML");
                    return;
                }
            }

            $timestampSwtId = 'eventScraping:' . implode(':', [
                    'sport:' . $this->message->data->sport,
                    'provider:' . $this->message->data->provider,
                    'schedule:' . $this->message->data->schedule
                ]);

            $doesExist = false;
            foreach (SwooleHandler::table('eventScrapingTable') as $key => $value) {
                if ($key == $timestampSwtId) {
                    $doesExist = true;
                    break;
                }
            }
            if ($doesExist) {
                $swooleTS = SwooleHandler::getValue('eventScrapingTable', $timestampSwtId)['value'];

                if ($swooleTS > $this->message->request_ts) {
                    Log::info("Event Transformation ignored - Old Timestamp");
                    return;
                }
            }

            SwooleHandler::setColumnValue('eventScrapingTable', $timestampSwtId, 'value', $this->message->request_ts);

            /** LOOK-UP TABLES */
            $activeEventsTable = SwooleHandler::table('activeEventsTable');

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
            if (!SwooleHandler::exists('providersTable', $providerSwtId)) {
                Log::info("Leagues Transformation ignored - Provider doesn't exist");
                return;
            } else {
                $provider = SwooleHandler::getValue('providersTable', $providerSwtId);
                $providerId = $provider['id'];
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
            if (!SwooleHandler::exists('sportsTable', $sportSwtId)) {
                Log::info("Events Transformation ignored - Sport doesn't exist");
                return;
            } else {
                $sports = SwooleHandler::getValue('sportsTable', $sportSwtId);
                $sportId = $sports['id'];
            }

            $activeEventsSwtId = implode(':', [
                'sId:' . $sportId,
                'pId:' . $providerId,
                'schedule:' . $this->message->data->schedule
            ]);

            $doesExist = false;
            foreach ($activeEventsTable as $key => $value) {
                if ($key == $activeEventsSwtId) {
                    $doesExist = true;
                    break;
                }
            }

            if ($doesExist) {
                $missingCountConfiguration = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT');

                $eventsJson   = $activeEventsTable->get($activeEventsSwtId);
                $events       = json_decode($eventsJson['events'], true);
                $activeEvents = $this->message->data->event_ids;

                if (is_null($events) || !is_array($events)) {
                    $events = [];
                }
                $inActiveEvents = array_diff($events, $activeEvents);

                $data = [];
                foreach ($inActiveEvents as $eventIdentifier) {
                    $eventTableKey = "sId:{$sportId}:pId:{$providerId}:eventIdentifier:{$eventIdentifier}";
                    $event         = SwooleHandler::getValue('eventRecordsTable', $eventTableKey);
                    if ($event) {
                        $missingCount = (int) $event['missing_count'] + 1;
                        SwooleHandler::setColumnValue('eventRecordsTable', $eventTableKey, 'missing_count', $missingCount);
                        if ($missingCount >= $missingCountConfiguration->value) {
                            $masterEvent = DB::table('master_events AS me')
                                             ->join('master_leagues AS ml', 'me.master_league_id', '=', 'ml.id')
                                             ->join('events as e', 'e.master_event_id', 'me.id')
                                             ->where('e.event_identifier', $event['event_identifier'])
                                             ->select('me.*', 'ml.name AS league_name', 'me.master_league_id')
                                             ->first();

                            if ($masterEvent) {
                                $inactiveEvent       = [
                                    'uid'           => $masterEvent->master_event_unique_id,
                                    'league_name'   => $masterEvent->league_name,
                                    'game_schedule' => $masterEvent->game_schedule
                                ];
                                $data[]              = $inactiveEvent;

                                SwooleHandler::setValue('inactiveEventsTable', 'unique:' . uniqid(), [
                                    'event' => json_encode($inactiveEvent)
                                ]);
                            }
                            $doesExist = SwooleHandler::exists('eventRecordsTable', $eventTableKey);
                            if ($doesExist) {
                                SwooleHandler::remove('eventRecordsTable', $eventTableKey);
                                if (($key = array_search($eventIdentifier, $this->message->data->event_ids)) !== false) {
                                    unset($this->message->data->event_ids[$key]);
                                }
                            }
                        } else {
                            $activeEvents[] = $eventIdentifier;
                        }
                    }
                }

                $activeEventsTable->set($activeEventsSwtId, ['events' => json_encode($activeEvents)]);

                if (!empty($data)) {
                    SwooleHandler::setValue('updateLeaguesTable', 'updateLeagues', ['value' => 1]);
                }
                Log::info("For Removal Event - Processed");
            }

            $endTime         = microtime(TRUE);
            $timeConsumption = $endTime - $startTime;

            Log::channel('scraping-events')->info([
                'request_uid'      => json_encode($this->message->request_uid),
                'request_ts'       => json_encode($this->message->request_ts),
                'offset'           => json_encode($this->offset),
                'time_consumption' => json_encode($timeConsumption),
                'events'           => json_encode($this->message->data->event_ids),
            ]);
        } catch (Exception $e) {
            Log::error(json_encode(
                [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file'    => $e->getFile(),
                ]
            ));
        }
    }
}
