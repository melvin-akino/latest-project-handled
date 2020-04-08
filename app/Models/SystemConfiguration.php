<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    protected $table = 'system_configurations';

    protected $fillable = [
        'type',
        'value',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public static function getProviderAccountSettings() 
    {
        return self::where('module', 'ProviderAccount')->get()->toArray();   
    }
}
