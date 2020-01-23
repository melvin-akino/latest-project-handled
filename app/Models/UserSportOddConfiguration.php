<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Exception;

class UserSportOddConfiguration extends Model
{
    protected $table = 'user_sport_odd_configurations';

    protected $fillable = [
        'user_id',
        'sport_odd_type_id',
        'active'
    ];

    /**
     * @param array $request
     * @return bool
     */
    public static function saveSettings(array $request): bool
    {
        try {
            DB::beginTransaction();

            $sportOddTypes = SportOddType::getEnabledSportOdds();

            $requestSportOddTypes = array_column($request, 'sport_odd_type_id');

            foreach ($sportOddTypes as $sportOddType) {
                if (in_array($sportOddType->id, $requestSportOddTypes)) {
                    $requestSportOddTypeKey = array_search($sportOddType->id, $requestSportOddTypes);

                    self::updateOrCreate(
                        [
                            'user_id' => auth()->user()->id,
                            'sport_odd_type_id' => $request[$requestSportOddTypeKey]['sport_odd_type_id'],
                        ],
                        [
                            'active' => $request[$requestSportOddTypeKey]['active'],
                            'updated_at' => Carbon::now()
                        ]
                    );
                }
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            return false;
        }
    }

    public static function getSportOddConfiguration()
    {
        $sql = "SELECT sport_odd_type_id, sport_id, sport, odd_type_id, type, active
                    FROM user_sport_odd_configurations as usoc
                    JOIN sport_odd_type as sot ON sot.id = usoc.sport_odd_type_id
                    JOIN sports as s ON s.id = sot.sport_id
                    JOIN odd_types as ot ON ot.id = sot.odd_type_id
                    WHERE usoc.user_id = ?";
        return DB::select($sql, [auth()->user()->id]);
    }

    public static function getInactiveSportOdds()
    {
        return self::where('active', false)
            ->where('user_id', auth()->user()->id)
            ->orderBy('sport_odd_type_id', 'asc');
    }
}
