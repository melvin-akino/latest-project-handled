<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterEventLink extends Model
{
    protected $table = "master_event_links";

    protected $fillable = [
        'event_id',
        'master_event_unique_id'
    ];

    protected $primaryKey = null;
    public $incrementing = false;
}
