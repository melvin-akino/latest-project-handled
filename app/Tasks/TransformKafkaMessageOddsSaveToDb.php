<?php

namespace App\Tasks;

use App\Models\{EventMarket,
    Events,
    MasterEvent,
    MasterEventLink,
    MasterEventMarket,
    MasterEventMarketLink,
    MasterEventMarketLog};
use Illuminate\Support\Facades\Log;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Exception;

class TransformKafkaMessageOddsSaveToDb extends Task
{
    protected $message;
    protected $swoole;
    protected $subTasks = [];
    protected $updated = false;
    protected $uid = null;

    public function __construct($subTasks, $uid)
    {
        $this->subTasks = $subTasks;
        $this->uid = $uid;
    }

    public function handle()
    {
        $this->swoole = app('swoole');
        $this->eventData = $this->subTasks['event'];
        $this->eventMarketsData = $this->subTasks['event-market'];
        $this->updatedOddsData = $this->subTasks['updated-odds'];
        $this->uid = $this->uid;

        try {
            $masterEventModel = MasterEvent::withTrashed()->updateOrCreate([
                'master_event_unique_id' => $this->eventData['MasterEvent']['data']['master_event_unique_id']
            ], $this->eventData['MasterEvent']['data']);
        } catch (Exception $e) {
            MasterEvent::where('master_event_unique_id',
                $this->eventData['MasterEvent']['data']['master_event_unique_id'])
                ->update($this->eventData['MasterEvent']['data']);
            $masterEventModel = MasterEvent::where('master_event_unique_id',
                $this->eventData['MasterEvent']['data']['master_event_unique_id'])->first();
        }

        $eventModel = Events::updateOrCreate([
            'event_identifier' => $this->eventData['Event']['data']['event_identifier']
        ], $this->eventData['Event']['data']);
        $rawEventId = $eventModel->id;

        $masterEventLink = MasterEventLink::updateOrCreate([
            'event_id'               => $rawEventId,
            'master_event_unique_id' => $masterEventModel->master_event_unique_id
        ], []);

        if ($masterEventModel && $eventModel && $masterEventLink) {
            $masterEventData = [
                'master_event_unique_id' => $this->eventData['MasterEvent']['data']['master_event_unique_id'],
                'master_league_name'     => $this->eventData['MasterEvent']['data']['master_league_name'],
                'master_home_team_name'  => $this->eventData['MasterEvent']['data']['master_home_team_name'],
                'master_away_team_name'  => $this->eventData['MasterEvent']['data']['master_away_team_name'],
            ];

            $this->swoole->eventsTable->set($this->eventData['MasterEvent']['swtKey'], $masterEventData);
        }

        if (!empty($this->eventMarketsData)) {
            foreach ($this->eventMarketsData as $eventMarket) {
                $masterEventMarketModel = MasterEventMarket::updateOrCreate([
                    'master_event_market_unique_id' => $eventMarket['MasterEventMarket']['data']['master_event_market_unique_id']
                ], $eventMarket['MasterEventMarket']['data']);


                if ($masterEventMarketModel) {
                    $masterEventMarketId = $masterEventMarketModel->id;

                    $eventMarketModel = EventMarket::updateOrCreate([
                        'bet_identifier' => $eventMarket['EventMarket']['data']['bet_identifier']
                    ], $eventMarket['EventMarket']['data']);
                    $eventMarketId = $eventMarketModel->id;

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
            array_map(function ($odds) use ($uid) {
                try {
                    EventMarket::updateOrCreate([
                        'bet_identifier' => $this->marketId
                    ], [
                        'odds' => $this->odds
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
