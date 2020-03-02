<?php

namespace App\Jobs;

use App\Models\MasterEventMarket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class MasterEventMarketCreation implements ShouldQueue
{
    use Dispatchable;

    public function __construct(array $data, string $swtKey, array $toInsert)
    {
        $this->data = $data;
        $this->swtKey = $swtKey;
        $this->toInsert = $toInsert;
    }

    public function handle()
    {
        $eventmarketModel = MasterEventMarket::create($this->data);
        $rawEventMarketId = $eventmarketModel->id;
        app('swoole')->eventMarketsTable[$this->swtKey]['id'] = $rawEventMarketId;
    }
}

//$toInsert['MasterEventMarket'][] = [
//    'sport_id'                      => $sportId,
//    'master_event_unique_id'        => $uid,
//    'odd_type_id'                   => $oddTypeId,
//    'master_event_market_unique_id' => $memUID,
//    'is_main'                       => $array['is_main'],
//    'market_flag'                   => $array['market_flag'],
//];
//
//$toInsert['EventMarket'][] = [
//    'provider_id'            => $providerId,
//    'master_event_unique_id' => $uid,
//    'odd_type_id'            => $oddTypeId,
//    'odds'                   => $markets->odds,
//    'odd_label'              => $array['odd_label'],
//    'bet_identifier'         => $markets->market_id,
//    'is_main'                => $array['is_main'],
//    'market_flag'            => $array['market_flag'],
//];
//
//$toInsert['MasterEventMarketLink'][] = [
//    'event_market_id'        => "",
//    'master_event_market_id' => "",
//];
//
//$toInsert['MasterEventMarketLog'][] = [
//    'master_event_unique_id' => $uid,
//    'odd_type_id'            => $oddTypeId,
//    'odds'                   => $markets->odds,
//    'odd_label'              => $array['odd_label'],
//    'is_main'                => $array['is_main'],
//    'market_flag'            => $array['market_flag'],
//];
