<?php

namespace App\Handlers;

use App\Models\{EventMarket,
    Events,
    MasterEvent,
    MasterEventMarket,
    MasterEventMarketLog,
    MasterLeague,
    Game
};

use Exception;
use Illuminate\Support\Facades\{DB, Log};
use Carbon\Carbon;
use App\Facades\SwooleHandler;

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
    public function init(array $subTasks = [], string $uid = null, array $dbOptions)
    {
        $this->subTasks  = $subTasks;
        $this->uid       = $uid;
        $this->dbOptions = $dbOptions;
        return $this;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $start   = microtime(true);
        $debug[] = 'start handle -> ' . $start;

        $this->swoole               = app('swoole');
        $this->eventData            = $this->subTasks['event'];
        $this->eventRawData         = $this->subTasks['event-raw'];
        $this->eventMarketsData     = $this->subTasks['event-market'] ?? [];
        $this->updatedOddsData      = $this->subTasks['updated-odds'] ?? [];
        $this->removeEventMarket    = $this->subTasks['remove-event-market'] ?? [];
        $this->removePreviousMarket = $this->subTasks['remove-previous-market'] ?? false;

        try {
            $start = microtime(true);
            DB::beginTransaction();

            $debug[] = 'deleting existing event markets -> ' . (microtime(true) - $start);
            $event   = DB::table('events')
                         ->where('event_identifier', $this->eventRawData['Event']['data']['event_identifier'])
                         ->whereNull('deleted_at')
                         ->first();

            if ($event && $this->removePreviousMarket || $this->dbOptions['event-only']) {
                EventMarket::deleteByEventId($event->event_id);
            }

            $debug[] = 'deleting event specific market -> ' . (microtime(true) - $start);
            if (!empty($this->removeEventMarket)) { // Only specific market type has no data
                foreach ($this->removeEventMarket as $removeEventMarket) {
                    EventMarket::deleteByParameters($removeEventMarket);
                }
            }
            $debug[] = 'checking if in masterlist -> ' . (microtime(true) - $start);
            if ($this->dbOptions['in-masterlist']) {
                $debug[]         = 'search and update master event -> ' . (microtime(true) - $start);
                $masterEventData = $this->eventData['MasterEvent']['data'];

                $doesExist = false;
                foreach ($this->swoole->eventsTable as $key => $event) {
                    if (
                        $event['master_league_id'] == $this->eventData['MasterEvent']['data']['master_league_id'] &&
                        $event['master_team_home_id'] == $this->eventData['MasterEvent']['data']['master_team_home_id'] &&
                        $event['master_team_away_id'] == $this->eventData['MasterEvent']['data']['master_team_away_id'] &&
                        $event['ref_schedule'] == $this->eventData['MasterEvent']['data']['ref_schedule']
                    ) {
                        $masterEventId       = $event['id'];
                        $masterEventUniqueId = $event['master_event_unique_id'];
                        $doesExist           = true;
                        break;
                    }
                }

                if ($doesExist) {
                    unset($masterEventData['master_league_id']);
                    unset($masterEventData['master_team_home_id']);
                    unset($masterEventData['master_team_away_id']);
                    unset($masterEventData['master_event_unique_id']);
                    DB::table('master_events')
                      ->where('master_league_id', $this->eventData['MasterEvent']['data']['master_league_id'])
                      ->where('master_team_home_id', $this->eventData['MasterEvent']['data']['master_team_home_id'])
                      ->where('master_team_away_id', $this->eventData['MasterEvent']['data']['master_team_away_id'])
                      ->where('ref_schedule', $this->eventData['MasterEvent']['data']['ref_schedule'])
                      ->update($masterEventData);
                } else {
                    $masterEventId       = DB::table('master_events')->insertGetId($masterEventData);
                    $masterEventUniqueId = $this->eventData['MasterEvent']['data']['master_event_unique_id'];
                }

                $this->eventRawData['Event']['data']['master_event_id'] = $masterEventId;
            }

            $debug[] = 'update or create events -> ' . (microtime(true) - $start);

            $doesExist = SwooleHandler::doesExistValue('eventsTable', 'event_identifier', $this->eventRawData['Event']['data']['event_identifier']);
            if ($doesExist) {
                $eventId = SwooleHandler::doesExistGetKeyValue('rawEventsTable', 'event_identifier', $this->eventRawData['Event']['data']['event_identifier'], 'id');
                DB::table('events')->where('event_identifier', $this->eventRawData['Event']['data']['event_identifier'])->update($this->eventRawData['Event']['data']);
            } else {
                $eventId = DB::table('events')->insertGetId($this->eventRawData['Event']['data']);
            }

            if ($eventId) {
                if (!empty($this->eventMarketsData)) {
                    $debug[] = 'looping event markets data -> ' . (microtime(true) - $start);
                    foreach ($this->eventMarketsData as $eventMarket) {
                        if (
                            in_array($eventMarket['EventMarket']['data']['odd_type_id'], [1, 10]) &&
                            $eventMarket['EventMarket']['data']['is_main'] == false
                        ) {
                            continue;
                        }

                        if ($this->dbOptions['in-masterlist'] && $masterEventId) {
                            $eventMarket['MasterEventMarket']['data']['master_event_id'] = $masterEventId;

                            $newMasterEvent = true;

                            if (empty($eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'])) {
                                $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'] = uniqid();
                                $debug[]                                                                   = 'search existing master_event_markets -> ' . (microtime(true) - $start);
                                $existingMasterEventMarket                                                 = MasterEventMarket::getExistingMemUID($masterEventId,
                                    $eventMarket['EventMarket']['data']['odd_type_id'],
                                    $eventMarket['EventMarket']['data']['odd_label'],
                                    $eventMarket['EventMarket']['data']['market_flag']
                                );

                                if ($existingMasterEventMarket) {
                                    $newMasterEvent                                                            = false;
                                    $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'] = $existingMasterEventMarket->master_event_market_unique_id;
                                }
                            }

                            $debug[] = 'update or create master event market -> ' . (microtime(true) - $start);
                            if ($newMasterEvent) {
                                $doesExist = SwooleHandler::doesExistValue('eventMarketsTable', 'master_event_market_unique_id', $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id']);
                                if ($doesExist) {
                                    $masterEventMarketId = SwooleHandler::doesExistGetKeyValue('eventMarketsTable', 'master_event_market_unique_id', $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'], 'id');
                                    DB::table('master_event_markets')->where('master_event_market_unique_id', $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'])->update($eventMarket['MasterEventMarket']['data']);
                                } else {
                                    $masterEventMarketId = DB::table('master_event_markets')->insertGetId($eventMarket['MasterEventMarket']['data']);
                                }
                            } else {
                                $masterEventMarketId = SwooleHandler::doesExistGetKeyValue('eventMarketsTable', 'master_event_market_unique_id', $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'], 'id');
                            }
                        }

                        $eventMarket['EventMarket']['data']['event_id'] = $eventId;

                        if ($this->dbOptions['in-masterlist'] && $masterEventMarketId) {
                            $eventMarket['EventMarket']['data']['master_event_market_id'] = $masterEventMarketId;
                        }

                        $doesExist = SwooleHandler::doesExistValue('rawEventMarketsTable', 'bet_identifier', $eventMarket['EventMarket']['data']['bet_identifier']);


                        $debug[] = 'deleting event market by parameters -> ' . (microtime(true) - $start);
                        DB::table('event_markets')
                          ->where('event_id', $eventMarket['EventMarket']['data']['event_id'])
                          ->where('odd_label', $eventMarket['EventMarket']['data']['odd_label'])
                          ->where('odd_type_id', $eventMarket['EventMarket']['data']['odd_type_id'])
                          ->where('market_flag', $eventMarket['EventMarket']['data']['market_flag'])
                          ->where('provider_id', $eventMarket['EventMarket']['data']['provider_id'])
                          ->update(['deleted_at' => Carbon::now()]);

                        $debug[] = 'update or create event markets -> ' . (microtime(true) - $start);
                        if ($doesExist) {
                            EventMarket::withTrashed()
                                       ->where('bet_identifier', $eventMarket['EventMarket']['data']['bet_identifier'])
                                       ->update($eventMarket['EventMarket']['data']);
                        } else {
                            $doesExist = false;
                            foreach ($this->swoole->rawEventMarkets as $key => $value) {
                                if (
                                    $value['event_id'] == $eventMarket['EventMarket']['data']['event_id'] &&
                                    $value['odd_label'] == $eventMarket['EventMarket']['data']['odd_label'] &&
                                    $value['odd_type_id'] == $eventMarket['EventMarket']['data']['odd_type_id'] &&
                                    $value['market_flag'] == $eventMarket['EventMarket']['data']['market_flag'] &&
                                    $value['provider_id'] == $eventMarket['EventMarket']['data']['provider_id']
                                ) {
                                    $doesExist = true;
                                    break;
                                }
                            }
                            if ($doesExist) {
                                EventMarket::withTrashed()
                                           ->where('event_id', $eventMarket['EventMarket']['data']['event_id'])
                                           ->where('odd_label', $eventMarket['EventMarket']['data']['odd_label'])
                                           ->where('odd_type_id', $eventMarket['EventMarket']['data']['odd_type_id'])
                                           ->where('market_flag', $eventMarket['EventMarket']['data']['market_flag'])
                                           ->where('provider_id', $eventMarket['EventMarket']['data']['provider_id'])
                                           ->update($eventMarket['EventMarket']['data']);
                            } else {
                                DB::table('event_markets')->insert($eventMarket['EventMarket']['data']);
                            }
                        }

                        $debug[] = 'create master event market logs -> ' . (microtime(true) - $start);
                        if ($this->dbOptions['in-masterlist'] && !empty($eventMarket['MasterEventMarketLog'])) {
                            $eventMarket['MasterEventMarketLog']['data']['master_event_market_id'] = $masterEventMarketId;

                            $masterEventMarketLog = DB::table('master_event_market_logs')
                                                      ->where('master_event_market_id', $masterEventMarketId)
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

                    $debug[] = 'Saving update odds data -> ' . (microtime(true) - $start);
                    if (!empty($this->updatedOddsData)) {
                        $eventRawData = $this->eventRawData;

                        array_map(function ($marketOdds) use ($eventRawData) {
                            Game::updateOddsData($marketOdds, $eventRawData['Event']['data']['provider_id']);
                        }, $this->updatedOddsData);
                    }
                }
            }

            DB::commit();

            $debug[] = 'delete all event markets in SWT -> ' . (microtime(true) - $start);
            if ($event && $this->removePreviousMarket || $this->dbOptions['event-only']) {
                foreach ($this->swoole->eventMarketsTable as $emKey => $emRow) {
                    if ($eventId == $emRow['event_id']) {
                        $this->swoole->eventMarketsTable->del($emKey);
                    }
                }
            }

            $debug[] = 'update event markets SWT -> ' . (microtime(true) - $start);
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
                            'event_id'                      => $eventId
                        ];

                        $this->swoole->eventMarketsTable->set($eventMarket['MasterEventMarket']['swtKey'], $array);
                    }
                }
            }

            $debug[] = 'update master event swt -> ' . (microtime(true) - $start);
            if ($this->dbOptions['in-masterlist'] && $masterEventId && $eventId) {
                $masterEventData = [
                    'master_event_unique_id' => $masterEventUniqueId,
                    'master_league_id'       => $this->eventData['MasterEvent']['data']['master_league_id'],
                    'master_team_home_id'    => $this->eventData['MasterEvent']['data']['master_team_home_id'],
                    'master_team_away_id'    => $this->eventData['MasterEvent']['data']['master_team_away_id'],
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
            $debug[] = 'updating odds data in SWT -> ' . (microtime(true) - $start);
            if ($this->dbOptions['in-masterlist'] && !empty($this->updatedOddsData)) {
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

            Log::debug($debug);
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
