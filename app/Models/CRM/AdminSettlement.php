<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdminSettlement extends Model
{
    protected $table = "admin_settlements";

    protected $fillable = [
        'reason',
        'payload',
        'bet_id',
        'processed',
    ];


    public function transaction_log()
    {
        return $this->belongsTo(OrderLogs::class,'order_logs_id','id');

    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}