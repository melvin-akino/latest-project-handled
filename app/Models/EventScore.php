<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EventScore extends Model
{
    protected $table = "event_scores";
    protected $fillable = [
        'master_event_unique_id',
        'score'
    ];
    protected $primaryKey   = null;
    public    $incrementing = false;
    public    $timestamps   = false;

    public static function fillDataFromOrders(array $meUID = [])
    {
        return DB::insert("INSERT INTO event_scores (master_event_unique_id, score)
            SELECT DISTINCT master_event_unique_id, score
                FROM master_events
                WHERE master_event_unique_id IN ('" . implode("', '", $meUID) . "')
        ");

    }
}
