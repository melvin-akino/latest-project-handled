<?php

namespace App\Jobs;

use App\Facades\SwooleHandler;
use App\Models\{Events, SystemConfiguration};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Exception;
use Illuminate\Support\Facades\{Log, DB};

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

            if (env('APP_ENV') != "local") {
                $doesExist     = false;
                $swtRequestUID = null;
                foreach ($swoole->scraperRequestsTable as $key => $scraperRequestsTable) {
                    if ($key == 'type:events:requestUID:' . $this->message->request_uid) {
                        $swtRequestUID = $this->message->request_uid;
                        $doesExist     = true;
                        break;
                    }
                }
                if (!$doesExist) {
                    Log::info("Event Transformation ignored - Request UID is from ML");
                    return;
                }
            }

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
                $payloadProviderId = $providersTable->get($providerSwtId)['id'];
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
                'pId:' . $payloadProviderId,
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

                switch ($this->message->data->schedule) {
                    case 'inplay':
                        $missingCountConfiguration = SystemConfiguration::where('type', 'INPLAY_MISSING_MAX_COUNT_FOR_DELETION')->first();
                        break;
                    case 'today':
                        $missingCountConfiguration = SystemConfiguration::where('type', 'TODAY_MISSING_MAX_COUNT_FOR_DELETION')->first();
                        break;
                    case 'early':
                    default:
                        $missingCountConfiguration = SystemConfiguration::where('type', 'EARLY_MISSING_MAX_COUNT_FOR_DELETION')->first();
                        break;
                }

                $eventsJson   = $activeEventsTable->get($activeEventsSwtId);
                $events       = json_decode($eventsJson['events'], true);
                $activeEvents = $this->message->data->event_ids;

                $inActiveEvents = array_diff($events, $activeEvents);

                $data = [];
                foreach ($inActiveEvents as $eventIdentifier) {
                    $event = Events::where('event_identifier', $eventIdentifier)->first();
                    if ($event) {
                        $event->missing_count += 1;
                        if ($event->missing_count >= $missingCountConfiguration->value) {
                            $masterEvent = DB::table('master_events AS me')
                                             ->leftJoin('master_leagues AS ml', 'me.master_league_id', '=', 'ml.id')
                                             ->where('me.id', $event->master_event_id)
                                             ->select('me.*', 'ml.name AS league_name')
                                             ->first();

                            if ($masterEvent) {
                                $data[] = [
                                    'uid'           => $masterEvent->master_event_unique_id,
                                    'league_name'   => $masterEvent->league_name,
                                    'game_schedule' => $masterEvent->game_schedule,
                                ];
                            }

                            $eventTableKey = "sId:{$sportId}:pId:{$payloadProviderId}:eventIdentifier:{$eventIdentifier}";
                            $doesExist     = SwooleHandler::exists('eventRecordsTable', $eventTableKey);
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

                foreach ($swoole->wsTable as $key => $row) {
                    if (strpos($key, 'uid:') === 0 && $swoole->isEstablished($row['value'])) {
                        if (!empty($data)) {
                            $swoole->push($row['value'], json_encode(['getForRemovalEvents' => $data]));
                        }
                    }
                }
                Log::info("For Removal Event - Processed");
            }


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
