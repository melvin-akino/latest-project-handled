<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderErrors extends Model
{
    //
     protected $table = "provider_error_messages";
     protected $fillable = [
     	'message',
     	'error_message_id'
     ];

     protected $hidden = [
        'created_at',
        'updated_at',
    ];
    

}
