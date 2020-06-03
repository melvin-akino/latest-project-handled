<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};

class ProviderAccount extends Model
{
    protected $table = "provider_accounts";

    use SoftDeletes;

    protected $fillable = [
        'username',
        'password',
        'type',
        'punter_percentage',
        'provider_id',
        'is_enabled',
        'is_idle',
        'deleted_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function providers()
    {
        return $this->belongsTo('App\Models\Provider');
    }
}
