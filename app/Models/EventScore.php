<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventScore extends Model
{
    protected $table        = "event_scores";
    protected $fillable     = [
        'master_event_unique_id',
        'score'
    ];
    protected $primaryKey   = 'master_event_unique_id';
    public    $incrementing = false;
    public    $timestamps   = false;
}
