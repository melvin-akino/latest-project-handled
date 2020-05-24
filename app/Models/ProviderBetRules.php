<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderBetRules extends Model
{
    //
    protected $table = "provider_bet_rules";

    protected $fillable = [
        'event_id',
        'provider_account_id',
        'odd_type_id',
        'team_ground',
        'not_allowed_ground'
    ];
}
