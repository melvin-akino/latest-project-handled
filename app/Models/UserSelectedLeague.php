<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSelectedLeague extends Model
{
    protected $table = "user_selected_leagues";

    protected $fillable = [
        'user_id',
        'master_league_id',
        'sport_id',
        'game_schedule'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public static function getSelectedLeague(int $sportId, array $filters = [])
    {
        return $userSelectedLeague = DB::table(self::$table . ' as usl')
                    ->join('master_leagues as ml', 'ml.id', 'usl.master_league_id')
                    ->where('usl.sport_id', $this->sportId)
                    ->where('ml.name', $filters['name'])
                    ->where('usl.game_schedule', $filters['schedule'])
                    ->first();
    }
}
