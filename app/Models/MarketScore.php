<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarketScore extends Model
{
    protected $table = "market_scores";
    protected $fillable = [
        'bet_identifier',
        'score'
    ];
    protected $primaryKey   = null;
    public    $incrementing = false;
    public    $timestamps   = false;

    public static function fillDataFromOrders(array $marketIds = [])
    {
        return DB::insert("INSERT INTO market_scores (bet_identifier, score)
            SELECT DISTINCT em.bet_identifier, me.score
                FROM event_markets as em
                JOIN master_event_markets as mem ON mem.id = em.master_event_market_id
                JOIN master_events as me ON me.id = mem.master_event_id
                WHERE em.bet_identifier IN ('" . implode("', '", $marketIds) . "')
        ");

    }
}
