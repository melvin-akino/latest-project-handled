<?php

namespace App\Tasks;

use App\Models\{EventMarket,
    Events,
    MasterEvent,
    MasterEventLink,
    MasterEventMarket,
    MasterEventMarketLink,
    MasterEventMarketLog};
use Illuminate\Support\Facades\{DB, Log};
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
        'event-only'            => true,
        'is-event-new'          => true,
        'is-market-different'   => true
    ];

    public function __construct(array $subTasks = [], string $uid = null, array $dbOptions)
    {
        $this->subTasks  = $subTasks;
        $this->uid       = $uid;
        $this->dbOptions = $dbOptions;
    }

    public function handle()
    {
        $this->swoole           = app('swoole');
        $this->eventData        = $this->subTasks['event'];
        $this->eventRawData     = $this->subTasks['event-raw'];
        $this->eventMarketsData = $this->subTasks['event-market'];
        $this->updatedOddsData  = $this->subTasks['updated-odds'];
        $removeEventMarket      = $this->subTasks['remove-event-market'];

        try {
            DB::beginTransaction();

            $eventModel = Events::updateOrCreate([
                'event_identifier' => $this->eventRawData['Event']['data']['event_identifier']
            ], $this->eventRawData['Event']['data']);

            if (!$this->dbOptions['event-only']) {
                $masterEventModel = MasterEvent::withTrashed()->updateOrCreate([
                    'master_event_unique_id' => $this->eventData['MasterEvent']['data']['master_event_unique_id']
                ], $this->eventData['MasterEvent']['data']);

                if ($masterEventModel && $eventModel) {
                    $rawEventId = $eventModel->id;
                    $masterEventLink = MasterEventLink::updateOrCreate([
                        'event_id'               => $rawEventId,
                        'master_event_unique_id' => $this->eventData['MasterEvent']['data']['master_event_unique_id']
                    ], []);
                }

                if (!empty($this->eventMarketsData)) {
                    foreach ($this->eventMarketsData as $eventMarket) {
                        $eventMarketModel = EventMarket::withTrashed()->updateOrCreate([
                            'bet_identifier'         => $eventMarket['EventMarket']['data']['bet_identifier'],
                            'master_event_unique_id' => $eventMarket['EventMarket']['data']['master_event_unique_id']
                        ], $eventMarket['EventMarket']['data']);
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

                            if (!empty($this->dbOptions['is-market-different'])) {
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

            if ($this->dbOptions['is-empty-market-id']) {
                MasterEventMarket::where(function($cond) use ($removeEventMarket) {
                    $cond->where('master_event_market_unique_id', $removeEventMarket['master_event_market_unique_id'])
                        ->where('odd_type_id', $removeEventMarket['odd_type_id']);
                })->delete();

                EventMarket::where(function($cond) use ($removeEventMarket) {
                    $cond->where('master_event_unique_id', $removeEventMarket['uid'])
                        ->where('odd_type_id', $removeEventMarket['odd_type_id'])
                        ->where('is_main', $removeEventMarket['is_main'])
                        ->where('market_flag', $removeEventMarket['market_flag']);
                })->delete();
            }

            DB::commit();

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
                foreach ($this->eventMarketsData as $eventMarket) {
                    if ($this->dbOptions['is-empty-market-id']) {
                        $this->swoole->eventMarketsTable->del($removeEventMarket['swt_key']);
                    } else {
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
                $WSOddsSwtId = "updatedEvents:" . $uid;
                $this->swoole->wsTable->set($WSOddsSwtId, ['value' => json_encode($this->updatedOddsData)]);
            }
        } catch (Exception $e) {
            Log::info($e->getMessage());
            DB::rollBack();
        }
    }
}
