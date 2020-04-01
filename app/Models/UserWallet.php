<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{
    protected $table = "wallet";

    protected $fillable = [
        'user_id',
        'currency_id',
        'balance',
    ];

    public function Order() {
        return $this->hasMany('App\Models\Order', 'user_id', 'user_id');
    }
}