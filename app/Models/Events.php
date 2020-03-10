<?php

namespace App\Models;

use App\Models\{
    Sport,
    Provider
};
use Illuminate\Database\Eloquent\{Model, SoftDeletes};

class Events extends Model
{
    use SoftDeletes;

    protected $table = "events";

    protected $fillable = [
        'league_name',
        'sport_id',
        'provider_id',
        'event_identifier',
        'home_team_name',
        'away_team_name',
        'ref_schedule',
        'game_schedule',
        'deleted_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function scopeSearch($query, $search, $cols = null)
    {
        if (is_null($cols)) {
            $except = [
                array_search('deleted_at', $this->fillable),
                // array_search('gender', $this->fillable),
                // array_search('password', $this->fillable),
                // array_search('birth_date', $this->fillable),
                // array_search('currency_id', $this->fillable),
                // array_search('address', $this->fillable)
            ];

            $cols = array_except($this->fillable, $except);
        }

        foreach ($cols as $key => $value) {
            if ($key == 0) {
                $query->where($value, 'ILIKE', "%$search%");
            }

            $query->orWhere($value, 'ILIKE', "%$search%");
        }
        return $query;
    }

    public function sports()
    {
        return $this->belongsTo(Sport::class, 'sport_id');
    }

    public function providers()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }
}
