<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderBetTransaction extends Model
{
    protected $table = "provider_bet_transactions";

    protected $fillable = [
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
