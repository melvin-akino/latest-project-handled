<?php

namespace App\Handlers;

use App\Facades\SwooleHandler;
use Exception;
use Illuminate\Support\Facades\{Log, DB, Redis};

class EventsTransformationHandler
{
    protected $message;
    protected $offset;
    protected $swoole;
    protected $missingCountConfiguration;

    public function init($message, $offset, $swoole, $missingCountConfiguration)
    {
        $this->message                   = $message;
        $this->offset                    = $offset;
        $this->swoole                    = $swoole;
        $this->missingCountConfiguration = $missingCountConfiguration;

        return $this;
    }

    public function handle()
    {
        try {
            

            $startTime                 = microtime(TRUE);
            $activeEventsTable         = SwooleHandler::table('activeEventsTable');
            $missingCountConfiguration = $this->missingCountConfiguration;
            $activeEvents              = $this->message->data->event_ids;
            $topicTable                = SwooleHandler::table('topicTable');
            $providerEventMarketsTable = SwooleHandler::table('providerEventMarketsTable');

            if (env('APP_ENV') != "local") {
                if (!Redis::exists('type:events:requestUID:' . $this->message->request_uid)) {
                    appLog('info', "Events Transformation ignored - Request UID is not from ML");
                    $toLogs = [
                        "class"       => "EventsTransformationHandler",
                        "message"     => "Events Transformation ignored - Request UID is not from ML",
                        "module"      => "HANDLER_ERROR",
                        "status_code" => 400,
                    ];
                    monitorLog('monitor_handlers', 'error', $toLogs);

                    return;
                }
            }

            $timestampSwtId = 'eventScraping:' . implode(':', [
                    'sport:' . $this->message->data->sport,
                    'provider:' . $this->message->data->provider,
                    'schedule:' . $this->message->data->schedule
                ]);

            if (SwooleHandler::exists('eventScrapingTable', $timestampSwtId)) {
                $swooleTS = SwooleHandler::getValue('eventScrapingTable', $timestampSwtId)['value'];

                if ($swooleTS > $this->message->request_ts) {
                    $toLogs = [
                        "class"       => "EventsTransformationHandler",
                        "message"     => "Event Transformation ignored - Old Timestamp",
                        "module"      => "HANDLER",
                        "status_code" => 208,
                    ];
                    monitorLog('monitor_handlers', 'info', $toLogs);

                    return;
                }
            }

            SwooleHandler::setColumnValue('eventScrapingTable', $timestampSwtId, 'value', $this->message->request_ts);

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
                $toLogs = [
                    "class"       => "EventsTransformationHandler",
                    "message"     => "Leagues Transformation ignored - Provider doesn't exist",
                    "module"      => "HANDLER_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_handlers', 'error', $toLogs);

                return;
            } else {
                $provider   = SwooleHandler::getValue('providersTable', $providerSwtId);
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
                $toLogs = [
                    "class"       => "EventsTransformationHandler",
                    "message"     => "Events Transformation ignored - Sport doesn't exist",
                    "module"      => "HANDLER_ERROR",
                    "status_code" => 404,
                ];
                monitorLog('monitor_handlers', 'error', $toLogs);

                return;
            } else {
                $sports  = SwooleHandler::getValue('sportsTable', $sportSwtId);
                $sportId = $sports['id'];
            }

            $activeEventsSwtId = implode(':', [
                'sId:' . $sportId,
                'pId:' . $providerId,
                'schedule:' . $this->message->data->schedule
            ]);

            if (SwooleHandler::exists('activeEventsTable', $activeEventsSwtId)) {
                $eventsJson = SwooleHandler::getValue('activeEventsTable', $activeEventsSwtId);
                $events     = json_decode($eventsJson['events'], true);

                if (is_null($events) || !is_array($events)) {
                    $events = [];
                }
                $inActiveEvents = array_diff($events, $activeEvents);

                foreach ($inActiveEvents as $eventIdentifier) {
                    $eventTableKey = "sId:{$sportId}:pId:{$providerId}:eventIdentifier:{$eventIdentifier}";
                    $event         = SwooleHandler::getValue('eventRecordsTable', $eventTableKey);
                    if ($event) {
                        $missingCount = (int) $event['missing_count'] + 1;
                        if ($missingCount >= $missingCountConfiguration->value) {
                            $inactiveEvent = [
                                'uid'           => $event['master_event_unique_id'],
                                'game_schedule' => $event['game_schedule']
                            ];

                            SwooleHandler::setValue('inactiveEventsTable', 'unique:' . uniqid(), [
                                'event' => json_encode($inactiveEvent)
                            ]);

                            foreach ($topicTable as $k => $topic) {
                                if (strpos($topic['topic_name'], 'uid-' . $event['master_event_unique_id']) === 0) {
                                    SwooleHandler::remove('topicTable', $k);
                                }
                            }

                            if (SwooleHandler::exists('eventRecordsTable', $eventTableKey)) {
                                SwooleHandler::remove('eventRecordsTable', $eventTableKey);
                                Log::info("Deleting provider event markets for eventIdentifier" . $eventIdentifier);
                                foreach ($providerEventMarketsTable as $key => $eventMarket) {
                                    $marketEventIdentifierArray = explode(":", $key);
                                    $marketEventIdentifier = $marketEventIdentifierArray[0];
                                    if ($eventIdentifier == $marketEventIdentifier) {
                                        Log::info("Deleting provider event markets" . $key);
                                        $providerEventMarketsTable->del($key);
                                    }
                                }

                            }

                            SwooleHandler::setColumnValue('eventRecordsTable', $eventTableKey, 'missing_count', $missingCount);
                        } else {
                            $activeEvents[] = $eventIdentifier;
                        }
                    }
                }

                $activeEventsTable->set($activeEventsSwtId, ['events' => json_encode($activeEvents)]);
                $toLogs = [
                    "class"       => "EventsTransformationHandler",
                    "message"     => "For Removal Event - Processed",
                    "module"      => "HANDLER",
                    "status_code" => 200,
                ];
                monitorLog('monitor_handlers', 'info', $toLogs);
            }

            var_dump("Count after event process");
            var_dump($providerEventMarketsTable->count());

            $endTime         = microtime(TRUE);
            $timeConsumption = $endTime - $startTime;

            $toLogs = [
                "class"       => "EventsTransformationHandler",
                "message"     => [
                    'request_uid'      => json_encode($this->message->request_uid),
                    'request_ts'       => json_encode($this->message->request_ts),
                    'offset'           => json_encode($this->offset),
                    'time_consumption' => json_encode($timeConsumption),
                    'events'           => json_encode($activeEvents),
                ],
                "module"      => "HANDLER",
                "status_code" => 200,
            ];
            monitorLog('monitor_handlers', 'info', $toLogs);
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "EventsTransformationHandler",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "HANDLER_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_handlers', 'error', $toLogs);
        }
    }
}
