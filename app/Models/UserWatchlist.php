<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
