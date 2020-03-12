<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    protected $table = 'sports';

    protected $fillable = [
        'sport',
        'details',
        'priority',
        'is_enabled'
    ];

    public function oddTypes()
    {
        return $this->belongsToMany('App\Models\OddType');
    }

    public static function getActiveSports()
    {
        return self::where('is_enabled', true)
            ->orderBy('priority', 'asc');
    }

    public static function getNameByID(int $id)
    {
        $query = self::where('id', $id);

        if (!$query->exists()) {
            return false;
        }

        return $query->where('is_enabled', true)
            ->first()
            ->sport;
    }
}
