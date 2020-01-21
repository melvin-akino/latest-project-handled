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
}
