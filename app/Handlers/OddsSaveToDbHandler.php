<?php

namespace App\Handlers;

use App\Models\{
    EventMarket,
    Events,
    MasterEvent,
    MasterEventLink,
    MasterEventMarket,
    MasterEventMarketLink,
    MasterEventMarketLog
};

use Exception;
use Illuminate\Support\Facades\{DB, Log};
use Hhxsv5\LaravelS\Swoole\Task\Task;

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
        $previousMarkets            = [];

        try {
            DB::beginTransaction();

            foreach ($this->removeEventMarket AS $key => $_row) {
                EventMarket::where('event_identifier', $_row['event_identifier'])
                    ->where('odd_type_id', $_row['odd_type_id'])
                    ->where('provider_id', $_row['provider_id'])
                    ->where('market_flag', $_row['market_flag'])
                    ->delete();
            }

            $event = Events::withTrashed()->where('event_identifier',
                $this->eventRawData['Event']['data']['event_identifier'])->first();
            if ($event && $event->game_schedule != $this->eventRawData['Event']['data']['game_schedule']) {
                EventMarket::where('master_event_unique_id', $event->master_event_unique_id)
                    ->delete();
            }

            $eventModel = Events::withTrashed()->updateOrCreate([
                'event_identifier' => $this->eventRawData['Event']['data']['event_identifier']
            ], $this->eventRawData['Event']['data']);

            if (!$this->dbOptions['event-only']) {
                $masterEventModel = MasterEvent::withTrashed()->updateOrCreate([
                    'master_event_unique_id' => $this->eventData['MasterEvent']['data']['master_event_unique_id']
                ], $this->eventData['MasterEvent']['data']);

                if ($masterEventModel && $eventModel) {
                    $rawEventId      = $eventModel->id;
                    $masterEventLink = MasterEventLink::updateOrCreate([
                        'event_id'               => $rawEventId,
                        'master_event_unique_id' => $this->eventData['MasterEvent']['data']['master_event_unique_id']
                    ], []);
                }

                if (!empty($this->eventMarketsData)) {
                    foreach ($this->eventMarketsData as $eventMarket) {
                        $eventMarketModel = EventMarket::withTrashed()->updateOrCreate(
                            [
                                'bet_identifier'         => $eventMarket['EventMarket']['data']['bet_identifier'],
                                'master_event_unique_id' => $eventMarket['EventMarket']['data']['master_event_unique_id']
                            ], $eventMarket['EventMarket']['data']
                        );

                        $eventMarketId = $eventMarketModel->id;

                        $masterEventMarketLink = MasterEventMarketLink::where('event_market_id', $eventMarketId);
                        $hasMasterEventMarketLink = false;
                        if ($masterEventMarketLink->exists()) {
                            $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id'] = ($masterEventMarketLink->first())->master_event_market_unique_id;
                            $hasMasterEventMarketLink = true;
                        }

                        $masterEventMarketModel = MasterEventMarket::updateOrCreate([
                            'master_event_market_unique_id' => $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id']
                        ], $eventMarket['MasterEventMarket']['data']);

                        if ($masterEventMarketModel) {
                            $masterEventMarketId = $masterEventMarketModel->id;

                            if (!$hasMasterEventMarketLink) {
                                MasterEventMarketLink::updateOrCreate([
                                    'event_market_id'               => $eventMarketId,
                                    'master_event_market_unique_id' => $masterEventMarketModel->master_event_market_unique_id
                                ], []);
                            }

                            if (!empty($eventMarket['MasterEventMarketLog'])) {
                                $eventMarket['MasterEventMarketLog']['data']['master_event_market_id'] = $masterEventMarketId;

                                MasterEventMarketLog::create($eventMarket['MasterEventMarketLog']['data']);
                            }
                        }
                    }
                }

                if (!empty($this->updatedOddsData)) {
                    $uid          = $this->uid;
                    $eventRawData = $this->eventRawData;

                    array_map(function ($marketOdds) use ($uid, $eventRawData) {
                        DB::table('event_markets as em')
                            ->join('master_event_market_links as meml', 'meml.event_market_id', 'em.id')
                            ->where('meml.master_event_market_unique_id', $marketOdds['market_id'])
                            ->where('em.provider_id', $eventRawData['Event']['data']['provider_id'])
                            ->update([
                                'em.odds' => $marketOdds['odds']
                            ]);
                    }, $this->updatedOddsData);
                }
            }

            DB::commit();

            if ($masterEventModel && $eventModel && $masterEventLink) {
                $masterEventData = [
                    'master_event_unique_id' => $this->eventData['MasterEvent']['data']['master_event_unique_id'],
                    'master_league_name'     => $this->eventData['MasterEvent']['data']['master_league_name'],
                    'master_home_team_name'  => $this->eventData['MasterEvent']['data']['master_home_team_name'],
                    'master_away_team_name'  => $this->eventData['MasterEvent']['data']['master_away_team_name'],
                    'game_schedule'          => $this->eventData['MasterEvent']['data']['game_schedule'],
                    'home_penalty'           => $this->eventData['MasterEvent']['data']['home_penalty'],
                    'away_penalty'           => $this->eventData['MasterEvent']['data']['away_penalty'],
                ];
                $this->swoole->eventsTable->set($this->eventData['MasterEvent']['swtKey'], $masterEventData);

                if ($this->dbOptions['is-event-new']) {
                    $additionalEventsSwtId = "additionalEvents:" . $this->eventData['MasterEvent']['data']['master_event_unique_id'];
                    $this->swoole->additionalEventsTable->set($additionalEventsSwtId, [
                        'value' => json_encode([
                            'sport_id'    => $this->eventRawData['Event']['data']['sport_id'],
                            'schedule'    => $this->eventRawData['Event']['data']['game_schedule'],
                            'league_name' => $this->eventData['MasterEvent']['data']['master_league_name']
                        ])
                    ]);
                }
            }

            if (!empty($this->removePreviousMarket)) {
                foreach ($this->removePreviousMarket AS $prevMarket) {
                    foreach ($this->swoole->eventMarketsTable AS $emKey => $emRow) {
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
