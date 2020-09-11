<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserWatchlist extends Model
{
    protected $table = "user_watchlist";

    protected $fillable = [
        'user_id',
        'master_event_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function getByUid(string $uid = '')
    {
        return DB::table('user_watchlist as uw')
                 ->join('master_events as me', 'uw.master_event_id', 'me.id')
                 ->where('master_event_unique_id', $uid)
                 ->select('uw.*');
    }
}
