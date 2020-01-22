<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProviderConfiguration extends Model
{
    protected $table = 'user_provider_configurations';

    protected $fillable = [
        'provider_id',
        'user_id',
        'punter_percentage',
        'active'
    ];
}
