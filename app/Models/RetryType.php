<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetryType extends Model
{
    protected $table = "retry_types";

    protected $fillable = [
        'type',
        'description',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
