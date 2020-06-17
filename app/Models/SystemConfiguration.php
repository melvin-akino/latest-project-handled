<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    protected $table = 'system_configurations';

    protected $fillable = [
        'type',
        'value',
        'module'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public static function getProviderAccountSettings()
    {
        return self::where('module', 'ProviderAccount')->get()->toArray();
    }

    public static function getSystemConfigurationValue (string $type, string $module = "")
    {
        $data = self::where('type', $type);

        if ($module != "") {
            $data = $data->where('module', $module);
        }

        return $data->first();
    }
}
