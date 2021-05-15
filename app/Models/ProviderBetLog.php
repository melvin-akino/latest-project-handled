<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderBetLog extends Model
{
    protected $table = 'provider_bet_logs';

    protected $fillable = [
        'provider_bet_id',
        'status',
        'created_at',
        'updated_at',
    ];

    protected static $logAttributes = [
        'provider_bet_id',
        'status',
    ];
}
