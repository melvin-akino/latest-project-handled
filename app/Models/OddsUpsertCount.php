<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OddsUpsertCount extends Model
{
    protected $table = "odds_upsert_counts";

    protected $fillable = [
        'type'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
