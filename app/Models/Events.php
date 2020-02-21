<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Events extends Model
{
    protected $table = "events";

    protected $fillable = [
        'league_id',
        'event_identifier',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
