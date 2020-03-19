<?php

namespace App\Tasks;

use App\Models\{EventMarket,
    Events,
    MasterEvent,
    MasterEventLink,
    MasterEventMarket,
    MasterEventMarketLink,
    MasterEventMarketLog};
use Illuminate\Support\Facades\{Log, DB};
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Exception;
use Carbon\Carbon;

class TransformKafkaMessageOddsSaveToDb extends Task
{
    protected $message;
    protected $swoole;
    protected $subTasks = [];
    protected $updated = false;
    protected $uid = null;
    protected $dbOptions = [
        'event-only' => true,
        'is-event-new' => true
    ];

    public function __construct(array $subTasks = [], string $uid = null, array $dbOptions)
    {
        $this->subTasks = $subTasks;
        $this->uid = $uid;
        $this->dbOptions = $dbOptions;
    }

    public function handle()
    {
        $this->swoole = app('swoole');
        $this->eventData = $this->subTasks['event'];
        $this->eventRawData = $this->subTasks['event-raw'];
        $this->eventMarketsData = $this->subTasks['event-market'];
        $this->updatedOddsData = $this->subTasks['updated-odds'];

        $eventModel = Events::updateOrCreate([
            'event_identifier' => $this->eventRawData['Event']['data']['event_identifier']
        ], $this->eventRawData['Event']['data']);

        if (!$this->dbOptions['event-only']) {
            try {
                $masterEventModel = MasterEvent::withTrashed()->updateOrCreate([
                    'master_event_unique_id' => $this->eventData['MasterEvent']['data']['master_event_unique_id']
                ], $this->eventData['MasterEvent']['data']);
            } catch (Exception $e) {
                MasterEvent::withTrashed()->where('master_event_unique_id',
                    $this->eventData['MasterEvent']['data']['master_event_unique_id'])
                    ->update($this->eventData['MasterEvent']['data']);
                $masterEventModel = MasterEvent::where('master_event_unique_id',
                    $this->eventData['MasterEvent']['data']['master_event_unique_id'])->first();
            }

            if ($masterEventModel && $eventModel) {
                $rawEventId = $eventModel->id;
                $masterEventLink = MasterEventLink::updateOrCreate([
                    'event_id'               => $rawEventId,
                    'master_event_unique_id' => $masterEventModel->master_event_unique_id
                ], []);
            }

            if ($masterEventModel && $eventModel && $masterEventLink) {
                $masterEventData = [
                    'master_event_unique_id' => $this->eventData['MasterEvent']['data']['master_event_unique_id'],
                    'master_league_name'     => $this->eventData['MasterEvent']['data']['master_league_name'],
                    'master_home_team_name'  => $this->eventData['MasterEvent']['data']['master_home_team_name'],
                    'master_away_team_name'  => $this->eventData['MasterEvent']['data']['master_away_team_name'],
                ];
                $this->swoole->eventsTable->set($this->eventData['MasterEvent']['swtKey'], $masterEventData);

                if ($this->dbOptions['is-event-new']) {
                    $additionalEventsSwtId = "additionalEvents:" . $this->eventData['MasterEvent']['data']['master_event_unique_id'];
                    $this->swoole->wsTable->set($additionalEventsSwtId, [
                        'value' => json_encode([
                            'sport_id' => $this->eventRawData['Event']['data']['sport_id'],
                            'schedule' => $this->eventRawData['Event']['data']['game_schedule'],
                            'uid'      => $this->eventData['MasterEvent']['data']['master_event_unique_id']
                        ])
                    ]);
                }
            }

            if (!empty($this->eventMarketsData)) {
                if (!empty($this->subTasks['delete-event-market'])) {
                    DB::table('event_markets')
                        ->where('master_event_unique_id', $this->subTasks['delete-event-market']['EventMarket']['master_event_unique_id'])
                        ->update([
                            'deleted_at' => Carbon::now()
                        ]);
                }



                foreach ($this->eventMarketsData as $eventMarket) {
                    try {
                        $eventMarketModel = EventMarket::updateOrCreate([
                            'bet_identifier' => $eventMarket['EventMarket']['data']['bet_identifier']
                        ], $eventMarket['EventMarket']['data']);
                    } catch (Exception $e) {
                        EventMarket::where('bet_identifier', $eventMarket['EventMarket']['data']['bet_identifier'])
                            ->update($eventMarket['EventMarket']['data']);
                        $eventMarketModel = EventMarket::where('bet_identifier', $eventMarket['EventMarket']['data']['bet_identifier'])->first();
                    }
                    $eventMarketId = $eventMarketModel->id;


                    $masterEventMarketLink = MasterEventMarketLink::where('event_market_id', $eventMarketId);
                    if ($masterEventMarketLink->exists()) {
                        $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'] = ($masterEventMarketLink->first())->master_event_market_unique_id;
                    }

                    $masterEventMarketModel = MasterEventMarket::updateOrCreate([
                        'master_event_market_unique_id' => $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id']
                    ], $eventMarket['MasterEventMarket']['data']);


                    if ($masterEventMarketModel) {
                        $masterEventMarketId = $masterEventMarketModel->id;

                        MasterEventMarketLink::updateOrCreate([
                            'event_market_id'               => $eventMarketId,
                            'master_event_market_unique_id' => $masterEventMarketModel->master_event_market_unique_id
                        ], []);

                        $eventMarket['MasterEventMarketLog']['data']['master_event_market_id'] = $masterEventMarketId;
                        MasterEventMarketLog::create($eventMarket['MasterEventMarketLog']['data']);

                        $array = [
                            'odd_type_id'                   => $eventMarket['MasterEventMarket']['data']['odd_type_id'],
                            'master_event_market_unique_id' => $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'],
                            'master_event_unique_id'        => $eventMarket['MasterEventMarket']['data']['master_event_unique_id'],
                            'provider_id'                   => $eventMarket['EventMarket']['data']['provider_id'],
                            'odds'                          => $eventMarket['EventMarket']['data']['odds'],
                            'odd_label'                     => $eventMarket['EventMarket']['data']['odd_label'],
                            'bet_identifier'                => $eventMarket['EventMarket']['data']['bet_identifier'],
                            'is_main'                       => $eventMarket['MasterEventMarket']['data']['is_main'],
                            'market_flag'                   => $eventMarket['MasterEventMarket']['data']['market_flag'],
                        ];

                        $this->swoole->eventMarketsTable->set($eventMarket['MasterEventMarket']['swtKey'], $array);
                    }
                }
            }

            if (!empty($this->updatedOddsData)) {
                $uid = $this->uid;
                array_map(function ($marketOdds) use ($uid) {
                    try {
                        EventMarket::where('bet_identifier', $marketOdds['market_id'])
                        ->update([
                            'odds' => $marketOdds['odds']
                        ]);
                    } catch (Exception $e) {
                        Log::error($e->getMessage());
                    }
                }, $this->updatedOddsData);
                $WSOddsSwtId = "updatedEvents:" . $uid;
                $this->swoole->wsTable->set($WSOddsSwtId, ['value' => json_encode($this->updatedOddsData)]);
            }
        }
    }
}
