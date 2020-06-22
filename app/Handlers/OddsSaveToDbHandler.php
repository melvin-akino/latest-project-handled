<?php

namespace App\Handlers;

use App\Models\{
    EventMarket,
    Events,
    MasterEvent,
    MasterEventMarket,
    MasterEventMarketLog,
    MasterLeague,
    Game
};

use Exception;
use Illuminate\Support\Facades\{DB, Log};

class OddsSaveToDbHandler
{
    protected $message;
    protected $swoole;
    protected $subTasks  = [];
    protected $updated   = false;
    protected $uid       = null;
    protected $dbOptions = [
        'event-only'          => true,
        'is-event-new'        => true,
        'is-market-different' => true
    ];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $subTasks = [], string $uid = null, array $dbOptions)
    {
        $this->subTasks  = $subTasks;
        $this->uid       = $uid;
        $this->dbOptions = $dbOptions;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->swoole               = app('swoole');
        $this->eventData            = $this->subTasks['event'];
        $this->eventRawData         = $this->subTasks['event-raw'];
        $this->eventMarketsData     = $this->subTasks['event-market'] ?? [];
        $this->updatedOddsData      = $this->subTasks['updated-odds'] ?? [];
        $this->removeEventMarket    = $this->subTasks['remove-event-market'] ?? [];
        $this->removePreviousMarket = $this->subTasks['remove-previous-market'] ?? [];

        try {
            DB::beginTransaction();

            $event = Events::withTrashed()->where('event_identifier',
                $this->eventRawData['Event']['data']['event_identifier'])->first();
            if ($event && $event->game_schedule != $this->eventData['MasterEvent']['data']['game_schedule'] || $this->dbOptions['event-only'] || $this->dbOptions['has-empty-market']) {
                EventMarket::where('event_id', $event->event_id)
                           ->delete();
            }

            $masterEvent = MasterEvent::withTrashed()->where('master_event_unique_id', $this->eventData['MasterEvent']['data']['master_event_unique_id']);
            if ($masterEvent->exists()) {
                $masterEventData = $this->eventData['MasterEvent']['data'];
                unset($masterEventData['master_league_id']);
                unset($masterEventData['master_team_home_id']);
                unset($masterEventData['master_team_away_id']);
            }

            $masterEventModel = MasterEvent::withTrashed()->updateOrCreate([
                'master_event_unique_id' => $this->eventData['MasterEvent']['data']['master_event_unique_id']
            ], $masterEventData);

            $this->eventRawData['Event']['data']['master_event_id'] = $masterEventModel->id;

            if ($masterEventModel) {
                $eventModel = Events::withTrashed()->updateOrCreate([
                    'event_identifier' => $this->eventRawData['Event']['data']['event_identifier']
                ], $this->eventRawData['Event']['data']);

                if ($eventModel) {
                    if (!empty($this->eventMarketsData)) {
                        foreach ($this->eventMarketsData as $eventMarket) {
                            if (
                                in_array($eventMarket['EventMarket']['data']['odd_type_id'], [1, 10]) &&
                                $eventMarket['EventMarket']['data']['is_main'] == false
                            ) {
                                continue;
                            }
                            $eventMarket['MasterEventMarket']['data']['master_event_id'] = $masterEventModel->id;

                            $newMasterEvent = true;

                            if (empty($eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'])) {
                                $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'] = uniqid();

                                $existingMasterEventMarket = MasterEventMarket::getExistingMemUID($masterEventModel->id,
                                    $eventMarket['EventMarket']['data']['odd_type_id'],
                                    $eventMarket['EventMarket']['data']['odd_label'],
                                    $eventMarket['EventMarket']['data']['market_flag']
                                );

                                if ($existingMasterEventMarket) {
                                    $newMasterEvent                                                            = false;
                                    $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'] = $existingMasterEventMarket->master_event_market_unique_id;
                                }
                            }

                            if ($newMasterEvent) {
                                $masterEventMarketModel = MasterEventMarket::updateOrCreate([
                                    'master_event_market_unique_id' => $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id']
                                ], $eventMarket['MasterEventMarket']['data']);
                            } else {
                                $masterEventMarketModel = MasterEventMarket::where('master_event_market_unique_id', $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'])->first();
                            }

                            if ($masterEventMarketModel) {
                                $masterEventMarketId = $masterEventMarketModel->id;

                                $eventMarket['EventMarket']['data']['event_id']               = $eventModel->id;
                                $eventMarket['EventMarket']['data']['master_event_market_id'] = $masterEventMarketId;

                                $eventMarketModel = EventMarket::where('bet_identifier', $eventMarket['EventMarket']['data']['bet_identifier'])->first();

                                EventMarket::where('event_id', $eventMarket['EventMarket']['data']['event_id'])
                                           ->where('odd_label', $eventMarket['EventMarket']['data']['odd_label'])
                                           ->where('odd_type_id', $eventMarket['EventMarket']['data']['odd_type_id'])
                                           ->where('market_flag', $eventMarket['EventMarket']['data']['market_flag'])
                                           ->where('provider_id', $eventMarket['EventMarket']['data']['provider_id'])
                                           ->delete();

                                if ($eventMarketModel) {
                                    EventMarket::withTrashed()->updateOrCreate(
                                        [
                                            'bet_identifier' => $eventMarket['EventMarket']['data']['bet_identifier'],
                                        ], $eventMarket['EventMarket']['data']
                                    );
                                } else {
                                    EventMarket::withTrashed()->updateOrCreate(
                                        [
                                            'event_id'    => $eventMarket['EventMarket']['data']['event_id'],
                                            'odd_label'   => $eventMarket['EventMarket']['data']['odd_label'],
                                            'odd_type_id' => $eventMarket['EventMarket']['data']['odd_type_id'],
                                            'market_flag' => $eventMarket['EventMarket']['data']['market_flag'],
                                            'provider_id' => $eventMarket['EventMarket']['data']['provider_id']
                                        ], $eventMarket['EventMarket']['data']
                                    );
                                }

                                if (!empty($eventMarket['MasterEventMarketLog'])) {
                                    $eventMarket['MasterEventMarketLog']['data']['master_event_market_id'] = $masterEventMarketId;

                                    $masterEventMarketLog = MasterEventMarketLog::where('master_event_market_id', $masterEventMarketId)
                                                                                ->orderBy('created_at', 'DESC');
                                    if ($masterEventMarketLog->count() > 0) {
                                        $masterEventMarketLogData = $masterEventMarketLog->first();
                                        if ($masterEventMarketLogData->odds != $eventMarket['MasterEventMarketLog']['data']['odds']) {
                                            MasterEventMarketLog::create($eventMarket['MasterEventMarketLog']['data']);
                                        }
                                    } else {
                                        MasterEventMarketLog::create($eventMarket['MasterEventMarketLog']['data']);
                                    }
                                }
                            }
                        }

                        if (!empty($this->updatedOddsData)) {
                            $eventRawData = $this->eventRawData;

                            array_map(function ($marketOdds) use ($eventRawData) {
                                Game::updateOddsData($marketOdds, $eventRawData['Event']['data']['provider_id']);
                            }, $this->updatedOddsData);
                        }
                    }
                }
            }

            DB::commit();

            if ($masterEventModel && $eventModel) {
                $masterEventData = [
                    'master_event_unique_id' => $masterEventModel->master_event_unique_id,
                    'master_league_id'       => $this->eventData['Event']['data']['master_league_id'],
                    'master_team_home_id'    => $this->eventData['Event']['data']['master_team_home_id'],
                    'master_team_away_id'    => $this->eventData['Event']['data']['master_team_away_id'],
                    'team_home_id'           => $this->eventData['Event']['data']['team_home_id'],
                    'team_away_id'           => $this->eventData['Event']['data']['team_away_id'],
                    'game_schedule'          => $this->eventData['MasterEvent']['data']['game_schedule'],
                    'home_penalty'           => $this->eventData['MasterEvent']['data']['home_penalty'],
                    'away_penalty'           => $this->eventData['MasterEvent']['data']['away_penalty'],
                    'sport_id'               => $this->eventData['MasterEvent']['data']['sport_id'],
                ];
                $this->swoole->eventsTable->set($this->eventData['MasterEvent']['swtKey'], $masterEventData);

                if ($this->dbOptions['is-event-new']) {

                    $masterLeague = MasterLeague::find($this->eventData['MasterEvent']['data']['master_league_id']);
                    if ($masterLeague) {
                        $additionalEventsSwtId = "additionalEvents:" . $this->eventData['MasterEvent']['data']['master_event_unique_id'];
                        $this->swoole->additionalEventsTable->set($additionalEventsSwtId, [
                            'value' => json_encode([
                                'sport_id'    => $this->eventRawData['Event']['data']['sport_id'],
                                'schedule'    => $this->eventData['MasterEvent']['data']['game_schedule'],
                                'league_name' => $masterLeague->name
                            ])
                        ]);
                    }
                }
            }

            if (!empty($this->removePreviousMarket)) {
                foreach ($this->removePreviousMarket as $prevMarket) {
                    foreach ($this->swoole->eventMarketsTable as $emKey => $emRow) {
                        if (strpos($emKey, $prevMarket['swtKey']) === 0) {
                            $this->swoole->eventMarketsTable->del($emKey);
                        }
                    }
                }
            }

            if (!empty($this->eventMarketsData)) {
                foreach ($this->eventMarketsData as $eventMarket) {
                    if (!empty($this->removeEventMarket)) {
                        $array = [
                            'odd_type_id'                   => $eventMarket['MasterEventMarket']['data']['odd_type_id'],
                            'master_event_market_unique_id' => $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'],
                            'master_event_unique_id'        => $eventMarket['MasterEvent']['data']['master_event_unique_id'],
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
                $uid         = $this->uid;
                $WSOddsSwtId = "updatedEvents:" . $uid;
                $this->swoole->updatedEventsTable->set($WSOddsSwtId, ['value' => json_encode($this->updatedOddsData)]);

                $updatedPrice = [];
                array_map(function ($updatedPriceValue) use ($updatedPrice) {
                    $eventIdentifier = $updatedPriceValue['event_identifier'];
                    unset($updatedPriceValue['event_identifier']);
                    $updatedPrice[$eventIdentifier][] = $updatedPriceValue;
                }, $this->updatedOddsData);

                $WSOddsSwtId = "updatedEventPrices:" . $uid;
                $this->swoole->updatedEventPricesTable->set($WSOddsSwtId,
                    ['value' => json_encode(array_values($updatedPrice))]);
            }
            Log::info('Transformation - Processed completed');
        } catch (Exception $e) {
            Log::error(json_encode(
                [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file'    => $e->getFile(),
                ]
            ));
            DB::rollBack();
        }
    }

    public function finish()
    {
        Log::info("Job: Odds Save to DB Done");
    }
}
