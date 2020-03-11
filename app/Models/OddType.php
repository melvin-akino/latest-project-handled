<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OddType extends Model
{
    protected $table = 'odd_types';

    protected $fillable = [
        'type'
    ];

    public function sports()
    {
        return $this->belongsToMany('App\Models\Sport');
    }

    public static function getTypeByID(int $id)
    {
        $query = self::where('id', $id);

        if (!$query->exists()) {
            return false;
        }

        return $query->first()
            ->type;
    }
}
