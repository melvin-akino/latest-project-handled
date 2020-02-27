<?php

use Illuminate\Database\Seeder;
use App\Models\{SportOddType, Sport};

class SportOddTypeSeeder extends Seeder
{
    private const ONEXTWO = 1;
    private const ML = 2;
    private const HDP = 3;
    private const OU = 4;
    private const OE = 5;
    private const HOMEGOALS = 6;
    private const AWAYGOALS = 7;
    private const HOMEGAME = 8;
    private const AWAYGAME = 9;
    private const HALFONEXTWO = 10;
    private const HALFHDP = 11;
    private const HALFOU = 12;
    private const HALFHOMEGOALS = 13;
    private const HALFAWAYGOALS = 14;
    private const FIRSTINNINGML = 15;
    private const FIRSTINNINGHDP = 16;
    private const FIRSTINNINGOU = 17;
    private const FIRSTSETML = 18;
    private const FIRSTSETHDP = 19;
    private const FIRSTSETOU = 20;
    private const SECONDSETML = 21;
    private const SECONDSETHDP = 22;
    private const SECONDSETOU = 23;
    private const THIRDSETML = 24;
    private const THIRDSETHDP = 25;
    private const THIRDSETOU = 26;
    private const FOURTHSETML = 27;
    private const FOURTHSETHDP = 28;
    private const FOURTHSETOU = 29;

    private const SOCCER = 1;
    private const BASKETBALL = 2;
    private const TENNIS = 3;
    private const BASEBALL = 4;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $sportOddTypeNames = [
            self::SOCCER     => [
                self::ONEXTWO       => ['FT 1X2', '1', '2'],
                self::HDP           => ['FT HDP', 'A/1', 'A/2'],
                self::OU            => ['FT O/U', '', ''],
                self::OE            => ['FT O/E', 'Odd', 'Even'],
                self::HALFONEXTWO   => ['HT 1X2', '1', '2'],
                self::HALFHDP       => ['HT HDP', 'A/1', 'A/2'],
                self::HALFOU        => ['HT O/U', '', ''],
                self::HALFHOMEGOALS => ['HOME GOALS', '1/O', '1/U'],
                self::HALFAWAYGOALS => ['AWAY GOALS', '2/O', '2/U']
            ],
            self::BASKETBALL => [
                self::ML        => ['MONEYLINE', '1', '2'],
                self::HDP       => ['HDP', 'A/1', 'A/2'],
                self::OU        => ['O/U', '', ''],
                self::HOMEGOALS => ['HOME GOALS', '1/O', '1/U'],
                self::AWAYGOALS => ['AWAY GOALS', '2/O', '2/U']
            ],
            self::TENNIS     => [
                self::ML           => ['MONEYLINE', '1', '2'],
                self::HDP          => ['HDP', 'A/P1', 'A/P2'],
                self::OU           => ['O/U', '', ''],
                self::HOMEGAME     => ['HOME GAME', '1/O', '1/U'],
                self::AWAYGAME     => ['AWAY GAME', '2/O', '2/U'],
                self::FIRSTSETML   => ['1st SET ML', '1', '2'],
                self::FIRSTSETHDP  => ['1st SET HDP', 'A/P1', 'A/P2'],
                self::FIRSTSETOU   => ['1st SET OU', '', ''],
                self::SECONDSETML  => ['2nd SET ML', '1', '2'],
                self::SECONDSETHDP => ['2nd SET HDP', 'A/P1', 'A/P2'],
                self::SECONDSETOU  => ['2nd SET OU', '', ''],
                self::THIRDSETML   => ['3rd SET ML', '1', '2'],
                self::THIRDSETHDP  => ['3rd SET HDP', 'A/P1', 'A/P2'],
                self::THIRDSETOU   => ['3rd SET OU', '', ''],
                self::FOURTHSETML  => ['4th SET ML', '1', '2'],
                self::FOURTHSETHDP => ['4th SET HDP', 'A/P1', 'A/P2'],
                self::FOURTHSETOU  => ['4th SET OU', '', ''],
            ],
            self::BASEBALL   => [
                self::ML             => ['MONEYLINE', '1', '2'],
                self::HDP            => ['HDP', 'A/1', 'A/2'],
                self::OU             => ['O/U', '', ''],
                self::FIRSTINNINGML  => ['Moneyline (1st Inning)', '1', '2'],
                self::FIRSTINNINGHDP => ['HDP (1st Inning)', 'A/1', 'A/2'],
                self::FIRSTINNINGOU  => ['O/U (1st Inning)', '', '']
            ]
        ];

        array_map(function ($sport) use ($sportOddTypeNames) {
            foreach ($sportOddTypeNames[$sport] as $key => $sportOddTypeName) {
                SportOddType::updateOrCreate([
                    'odd_type_id' => $key,
                    'sport_id'    => $sport,
                ], [
                    'name'       => $sportOddTypeName[0],
                    'home_label' => $sportOddTypeName[1],
                    'away_label' => $sportOddTypeName[2],
                ]);
            }
        }, [
            self::SOCCER,
            self::BASKETBALL,
            self::TENNIS,
            self::BASEBALL,
        ]);
    }
}
