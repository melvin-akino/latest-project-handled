<?php

namespace App\Jobs;

use App\Models\Events;
use App\Models\MasterEvent;
use App\Models\SystemConfiguration;
use App\Models\UserWatchlist;
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

            $doesExist     = false;
            $swtRequestUID = null;
            foreach ($swoole->scraperRequestsTable as $key => $scraperRequestsTable) {
                if ($key == 'type:events:requestUID:' . $this->message->request_uid) {
                    $swtRequestUID = $this->message->request_uid;
                    $doesExist     = true;
                }
            }
            if (!$doesExist) {
                Log::info("Event Transformation ignored - Request UID is from ML");
                return;
            } else {
                $swoole->scraperRequestsTable->del('type:events:requestUID:' . $swtRequestUID);
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
            $eventsTable       = $swoole->eventsTable;
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

            $providerPriority = 0;
            $providerId       = 0;
            foreach ($providersTable as $key => $provider) {
                if (empty($providerId) || $providerPriority > $provider['priority']) {
                    if ($provider['is_enabled']) {
                        $providerId       = $provider['id'];
                        $providerPriority = $provider['priority'];
                    }
                }
            }

            if (empty($providerId)) {
                Log::info("For Removal Event - No Providers Found");
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

                $eventsJson = $activeEventsTable->get($activeEventsSwtId);
                $events     = json_decode($eventsJson['events'], true);

                $inActiveEvents = array_diff($events, $this->message->data->event_ids);

                $data = [];
                foreach ($inActiveEvents as $eventIdentifier) {
                    $event = Events::where('event_identifier', $eventIdentifier)->first();
                    if ($event) {
                        $event->missing_count += 1;
                        $event->save();
                        if ($event->missing_count >= $missingCountConfiguration->value) {
                            $masterEvent = MasterEvent::find($event->master_event_id);
                            if ($masterEvent && $payloadProviderId == $providerId) {
                                if ($masterEvent) {
                                    UserWatchlist::where('master_event_id', $event->master_event_id)->delete();
                                    MasterEvent::where('id', $event->master_event_id)->delete();
                                    $data[] = $masterEvent->master_event_unique_id;
                                }
                            }

                            $eventTableKey = "sId:{$sportId}:pId:{$providerId}:eventIdentifier:{$event->id}";
                            $doesExist     = false;
                            foreach ($eventsTable as $k => $v) {
                                if ($k == $eventTableKey) {
                                    $doesExist = true;
                                    break;
                                }
                            }
                            if ($doesExist) {
                                $eventsTable->del($eventTableKey);
                                if (($key = array_search($eventIdentifier, $this->message->data->event_ids)) !== false) {
                                    unset($this->message->data->event_ids[$key]);
                                }
                            }

                            $event->delete();
                        }
                    }
                }

                $activeEventsTable->set($activeEventsSwtId, ['events' => json_encode($this->message->data->event_ids)]);

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
