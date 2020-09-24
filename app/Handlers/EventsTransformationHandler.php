<?php

namespace App\Handlers;

use App\Facades\SwooleHandler;
use App\Models\{SystemConfiguration, UserSelectedLeague};
use Exception;
use Illuminate\Support\Facades\{Log, DB};

class EventsTransformationHandler
{
    protected $message;
    protected $offset;
    protected $swoole;

    public function init($message, $offset)
    {
        $this->message = $message;
        $this->offset  = $offset;

        return $this;
    }

    public function handle()
    {
        try {
            $startTime = microtime(TRUE);

            $swoole             = app('swoole');
            $eventScrapingTable = $swoole->eventScrapingTable;

            if (env('APP_ENV') != "local") {
                $doesExist     = false;
                $swtRequestUID = null;
                foreach ($swoole->scraperRequestsTable as $key => $scraperRequestsTable) {
                    if ($key == 'type:events:requestUID:' . $this->message->request_uid) {
                        SwooleHandler::remove('scraperRequestsTable', $key);
                        $doesExist = true;
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
                $missingCountConfiguration = SystemConfiguration::getSystemConfigurationValue('EVENT_VALID_MAX_MISSING_COUNT');

                $eventsJson   = $activeEventsTable->get($activeEventsSwtId);
                $events       = json_decode($eventsJson['events'], true);
                $activeEvents = $this->message->data->event_ids;

                if (is_null($events) || !is_array($events)) {
                    $events = [];
                }
                $inActiveEvents = array_diff($events, $activeEvents);

                $data = [];
                if (!empty($inActiveEvents)) {
                    SwooleHandler::setValue('updateLeaguesTable', 'updateLeagues', ['value' => 1]);
                }
                foreach ($inActiveEvents as $eventIdentifier) {
                    $eventTableKey = "sId:{$sportId}:pId:{$payloadProviderId}:eventIdentifier:{$eventIdentifier}";
                    $event         = SwooleHandler::getValue('eventRecordsTable', $eventTableKey);
                    if ($event) {
                        $missingCount = (int) $event['missing_count'] + 1;
                        SwooleHandler::setColumnValue('eventRecordsTable', $eventTableKey, 'missing_count', $missingCount);
                        if ($missingCount > $missingCountConfiguration->value) {

                            $masterEvent = DB::table('master_events AS me')
                                             ->join('master_leagues AS ml', 'me.master_league_id', '=', 'ml.id')
                                             ->join('events as e', 'e.master_event_id', 'me.id')
                                             ->where('e.event_identifier', $event['event_identifier'])
                                             ->select('me.*', 'ml.name AS league_name', 'me.master_league_id')
                                             ->first();

                            if ($masterEvent) {
                                $data[]              = [
                                    'uid'           => $masterEvent->master_event_unique_id,
                                    'league_name'   => $masterEvent->league_name,
                                    'game_schedule' => $masterEvent->game_schedule
                                ];
                                $userSelectedLeagues = UserSelectedLeague::getSelectedLeagueByAllUsers([
                                    'league_id' => $masterEvent->master_league_id,
                                    'schedule'  => $this->message->data->schedule,
                                    'sport_id'  => $sportId
                                ]);

                                if ($userSelectedLeagues->exists()) {
                                    foreach ($userSelectedLeagues->get() as $userSelectedLeague) {
                                        $swtKey = 'userId:' . $userSelectedLeague->user_id . ':sId:' . $sportId . ':lId:' . $masterEvent->master_league_id . ':schedule:' . $this->message->data->schedule;

                                        if (SwooleHandler::exists('userSelectedLeaguesTable', $swtKey)) {
                                            SwooleHandler::remove('userSelectedLeaguesTable', $swtKey);
                                        }
                                    }
                                    $userSelectedLeagues->delete();
                                }
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
                    foreach ($swoole->wsTable as $key => $row) {
                        if (strpos($key, 'uid:') === 0 && $swoole->isEstablished($row['value'])) {
                            $swoole->push($row['value'], json_encode(['getForRemovalEvents' => $data]));
                        }
                    }
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
