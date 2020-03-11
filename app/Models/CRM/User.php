<?php

namespace App\Models\CRM;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class User extends Authenticatable
{
    protected $table = "crm_users";

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'status_id',
    ];

    protected $hidden = [
        'password',
        'created_at',
        'updated_at',
    ];
}
