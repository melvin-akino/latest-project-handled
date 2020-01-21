<?php

use Illuminate\Database\Seeder;
use App\Models\SportOddType;
use Carbon\Carbon;

class SportOddTypeSeeder extends Seeder
{
    private const FT1X1 = 1;
    private const FTHANDICAP = 2;
    private const FTOVERUNDER = 3;
    private const FTODDEVEN = 4;
    private const ONEH1X2 = 5;
    private const ONEHHANDICAP = 6;
    private const ONEHOVERUNDER = 7;

    private const SOCCER = 1;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $oddTypeIds = [
            self::FT1X1,
            self::FTHANDICAP,
            self::FTOVERUNDER,
            self::FTODDEVEN,
            self::ONEH1X2,
            self::ONEHHANDICAP,
            self::ONEHOVERUNDER
        ];

        foreach ($oddTypeIds as $oddTypeId) {
            SportOddType::create([
                'sport_id'    => self::SOCCER,
                'odd_type_id' => $oddTypeId,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now()
            ]);
        }
    }
}
