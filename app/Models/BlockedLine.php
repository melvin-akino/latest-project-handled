<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedLine extends Model
{
    protected $table = "blocked_lines";

    protected $fillable = [
        'event_id',
        'odd_type_id',
        'points',
        'line'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public static function getBlockedLines($eventId, $oddType, $points) {
        return self::where('event_id', $eventId)
            ->where('odd_type_id', $oddType)
            ->where('points', $points)
            ->select('line')
            ->pluck('line')
            ->toArray();
    }
}
