<?php

use Illuminate\Database\Seeder;
use App\Models\{SportOddType};

class SportOddTypeAdditionalSeeder extends Seeder
{
    private const HTOE = 16;
    private const FIRSTSETML = 16;
    private const FIRSTSETHDP = 17;
    private const FIRSTSETOU = 18;
    private const FIRSTSETHOMEGAME = 19;
    private const FIRSTSETAWAYGAME = 20;
    private const SECONDSETML = 21;
    private const SECONDSETHDP = 22;
    private const SECONDSETOU = 23;
    private const SECONDSETHOMEGAME = 24;
    private const SECONDSETAWAYGAME = 25;
    private const THIRDSETML = 26;
    private const THIRDSETHDP = 27;
    private const THIRDSETOU = 28;
    private const THIRDSETHOMEGAME = 29;
    private const THIRDSETAWAYGAME = 30;
    private const FOURTHSETML = 31;
    private const FOURTHSETHDP = 32;
    private const FOURTHSETOU = 33;
    private const FOURTHSETHOMEGAME = 34;
    private const FOURTHSETAWAYGAME = 35;

    private const FT1X2 = 1;
    private const OU = 3;
    private const HT1X2 = 5;
    private const HTOU = 7;

    private const TENNIS = 5;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $oddTypes = [
            self::FIRSTSETML        => ['1st Set ML', '1', '2'],
            self::FIRSTSETHDP       => ['1st Set HDP', 'A/P1', 'A/P2'],
            self::FIRSTSETOU        => ['1st Set O/U', '', ''],
            self::FIRSTSETHOMEGAME  => ['1st Set Home Game', '1/O', '1/U'],
            self::FIRSTSETAWAYGAME  => ['1st Set Away Game', '2/O', '2/U'],
            self::SECONDSETML       => ['2nd Set ML', '1', '2'],
            self::SECONDSETHDP      => ['2nd Set HDP', 'A/P1', 'A/P2'],
            self::SECONDSETOU       => ['2nd Set O/U', '', ''],
            self::SECONDSETHOMEGAME => ['2nd Set Home Game', '1/O', '1/U'],
            self::SECONDSETAWAYGAME => ['2nd Set Away Game', '2/O', '2/U'],
            self::THIRDSETML        => ['3rd Set ML', '1', '2'],
            self::THIRDSETHDP       => ['3rd Set HDP', 'A/P1', 'A/P2'],
            self::THIRDSETOU        => ['3rd Set O/U', '', ''],
            self::THIRDSETHOMEGAME  => ['3rd Set Home Game', '1/O', '1/U'],
            self::THIRDSETAWAYGAME  => ['3rd Set Away Game', '2/O', '2/U'],
            self::FOURTHSETML       => ['4th Set ML', '1', '2'],
            self::FOURTHSETHDP      => ['4th Set HDP', 'A/P1', 'A/P2'],
            self::FOURTHSETOU       => ['4th Set O/U', '', ''],
            self::FOURTHSETHOMEGAME => ['4th Set Home Game', '1/O', '1/U'],
            self::FOURTHSETAWAYGAME => ['4th Set Away Game', '2/O', '2/U'],
        ];

        foreach ($oddTypes as $key => $oddType) {
            SportOddType::updateOrCreate([
                'sport_id'          => self::TENNIS,
                'odd_type_id' => $key
            ], [
                'name'       => $oddType[0],
                'home_label' => $oddType[1],
                'away_label' => $oddType[2]
            ]);
        }

        // Remove label for OU and HT OU
        SportOddType::whereIn('odd_type_id', [self::OU, self::HTOU])->update([
            'home_label' => '',
            'away_label' => ''
        ]);

        SportOddType::whereIn('odd_type_id', [self::FT1X2, self::HT1X2])->update([
            'home_label' => '1',
            'away_label' => '2'
        ]);
    }
}
