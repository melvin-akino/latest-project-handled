<?php

namespace App\Tasks;

use App\Models\{EventMarket,
    Events,
    MasterEvent,
    MasterEventLink,
    MasterEventMarket,
    MasterEventMarketLink,
    MasterEventMarketLog};
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\{DB, Log};
use Exception;

class TransformationEventAndOddsCreation extends Task
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        try {
            DB::beginTransaction();
            $swoole = app('swoole');
            if (!MasterEvent::where('master_event_unique_id', $this->data['MasterEvent']['data']['master_event_unique_id'])->exists()) {
                $masterEventModel = MasterEvent::create($this->data['MasterEvent']['data']);

                $eventModel = Events::create($this->data['Event']['data']);
                $rawEventId = $eventModel->id;

                $masterEventLink = MasterEventLink::create([
                    'event_id' => $rawEventId,
                    'master_event_unique_id' => $masterEventModel->master_event_unique_id
                ]);

                if ($masterEventModel && $eventModel && $masterEventLink) {
                    $masterEventData = [
                        'master_event_unique_id' => $this->data['MasterEvent']['data']['master_event_unique_id'],
                        'master_league_name'     => $this->data['MasterEvent']['data']['master_league_name'],
                        'master_home_team_name'  => $this->data['MasterEvent']['data']['master_home_team_name'],
                        'master_away_team_name'  => $this->data['MasterEvent']['data']['master_away_team_name'],
                    ];

                    $swoole->eventsTable->set($this->data['MasterEvent']['swtKey'], $masterEventData);
                }
            }

            if (!empty($this->data['MasterEventMarket']['isNew'])) {
                $masterEventMarketModel = MasterEventMarket::create($this->data['MasterEventMarket']['data']);
            } else {
                $masterEventMarketModel = MasterEventMarket::where('master_event_market_unique_id', $this->data['MasterEventMarket']['data']['master_event_market_unique_id'])
                    ->first();
            }

            if ($masterEventMarketModel) {
                $masterEventMarketId = $masterEventMarketModel->id;

                $eventMarketModel = EventMarket::create($this->data['EventMarket']['data']);
                $eventMarketId = $eventMarketModel->id;

                MasterEventMarketLink::create([
                    'event_market_id' => $eventMarketId,
                    'master_event_market_unique_id' => $masterEventMarketModel->master_event_market_unique_id
                ]);

                $this->data['MasterEventMarketLog']['data']['master_event_market_id'] = $masterEventMarketId;
                MasterEventMarketLog::create($this->data['MasterEventMarketLog']['data']);

                $array = [
                    'odd_type_id'                   => $this->data['MasterEventMarket']['data']['odd_type_id'],
                    'master_event_market_unique_id' => $this->data['MasterEventMarket']['data']['master_event_market_unique_id'],
                    'master_event_unique_id'        => $this->data['MasterEventMarket']['data']['master_event_unique_id'],
                    'provider_id'                   => $this->data['EventMarket']['data']['provider_id'],
                    'odds'                          => $this->data['EventMarket']['data']['odds'],
                    'odd_label'                     => $this->data['EventMarket']['data']['odd_label'],
                    'bet_identifier'                => $this->data['EventMarket']['data']['bet_identifier'],
                    'is_main'                       => $this->data['MasterEventMarket']['data']['is_main'],
                    'market_flag'                   => $this->data['MasterEventMarket']['data']['market_flag'],
                ];

                $swoole->eventMarketsTable->set($this->data['MasterEventMarket']['swtKey'], $array);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
        }
    }
}
