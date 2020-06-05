<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderAccountOrder extends Model
{
    protected $table = "provider_account_orders";

    protected $fillable = [
        'order_log_id',
        'exchange_rate_id',
        'actual_stake',
        'actual_to_win',
        'actual_profit_loss',
        'exchange_rate',
        'created_at',
        'updated_at',
    ];

    public function order_logs() {
        return $this->belongsTo(App/Models/OrderLogs::class, 'id', 'order_log_id');
    }

    public function exchange_rates() {
        return $this->belongsTo(App/Models/ExchangeRate::class, 'id', 'exchange_rate_id');
    }
}
