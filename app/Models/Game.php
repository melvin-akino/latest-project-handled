<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Game extends Model
{

    public static function updateOddsData(array $marketOdds = [], int $providerId)
    {
        return DB::table('event_markets as em')
                ->join('master_event_markets as mem', 'mem.id', 'em.master_event_market_id')
                ->where('mem.master_event_market_unique_id', $marketOdds['market_id'])
                ->where('em.provider_id', $providerId)
                ->update([
                    'em.odds' => $marketOdds['odds']
                ]);
    }
}
