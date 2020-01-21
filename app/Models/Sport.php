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
        'is_enabled',
        'created_at',
        'updated_at'
    ];

    public function oddTypes()
    {
        return $this->belongsToMany('App\Models\OddType');
    }
}
