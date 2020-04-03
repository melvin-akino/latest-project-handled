<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = "currency";
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'name',
        'code',
        'symbol'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public static function getIdByCode(string $code)
    {
        $query = self::where('code', $code);

        if ($query->count() == 0) {
            return false;
        }

        return $query->first()->id;
    }

    public function exchange_rate()
    {
        return $this->hasMany(ExchangeRate::class);
    }
}
