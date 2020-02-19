<?php

use Illuminate\Database\Seeder;
use App\Models\SportOddType;

class SportOddTypeUpdateSeeder extends Seeder
{
    private const FT1X2 = 1;
    private const FTHANDICAP = 2;
    private const FTOVERUNDER = 3;
    private const FTODDEVEN = 4;
    private const ONEH1X2 = 5;
    private const ONEHHANDICAP = 6;
    private const ONEHOVERUNDER = 7;
    private const HOMEGOALS = 8;
    private const AWAYGOALS = 9;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $sportOddTypeNames = [
            self::FT1X2 => ['FT 1X2', '', ''],
            self::FTHANDICAP => ['FT HDP', 'A/1', 'A/2'],
            self::FTOVERUNDER => ['FT O/U', 'O', 'U'],
            self::FTODDEVEN => ['FT O/E', 'Odd', 'Even'],
            self::ONEH1X2 => ['HT 1X2', '', ''],
            self::ONEHHANDICAP => ['HT HDP', 'A/1', 'A/2'],
            self::ONEHOVERUNDER => ['HT O/U', 'O', 'U'],
            self::HOMEGOALS => ['HOME GOALS', '1/O', '1/U'],
            self::AWAYGOALS => ['AWAY GOALS', '2/O', '2/U']
        ];

        foreach ($sportOddTypeNames as $key => $sportOddTypeName) {
            SportOddType::updateOrCreate([
                'odd_type_id' => $key,
                'sport_id' => 1,
            ], [
                'name' => $sportOddTypeName[0],
                'home_label' => $sportOddTypeName[1],
                'away_label' => $sportOddTypeName[2],
            ]);
        }
    }
}
