<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $table = "exchange_rates";

    protected $fillable = [
        'from_currency_id',
        'to_currency_id',
        'default_amount',
        'exchange_rate'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
