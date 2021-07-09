<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class ProviderErrors extends Model
{
    protected $table = "provider_error_messages";

    protected $fillable = [
        'message',
        'error_message_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function Errorvalue()
    {
        return $this->belongsTo('App\Models\ErrorMessage','error_message_id');
    }

    public static function getProviderErrorMessage(int $id)
    {
        return DB::table('provider_error_messages as pem')
            ->leftJoin('error_messages as em', 'em.id', 'pem.error_message_id')
            ->leftJoin('retry_types as rt', 'rt.id', 'pem.retry_type_id')
            ->where('pem.id', $id)
            ->select('pem.id', 'em.error', 'pem.message as provider_error', 'rt.type as retry_type', 'odds_have_changed');
    }
}