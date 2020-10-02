<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ErrorMessage extends Model
{
    protected $table = "error_messages";

    protected $fillable = [
        'error'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
