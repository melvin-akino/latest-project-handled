<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderBetLog extends Model
{
    protected $table = "provider_bets";

    protected $fillable = [
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
