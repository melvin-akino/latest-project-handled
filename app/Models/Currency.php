<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table      = "currency";
    protected $primaryKey = 'id';
    protected $fillable   = [
        'id',
        'name',
        'code',
        'symbol'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
