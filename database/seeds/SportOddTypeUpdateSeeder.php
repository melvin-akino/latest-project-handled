<?php

use Illuminate\Database\Seeder;
use App\Models\{SportOddType, Sport};

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
    private const MONEYLINE = 10;
    private const FIRSTINNINGML = 11;
    private const FIRSTINNINGHDP = 12;
    private const FIRSTINNINGOU = 13;
    private const HOMEGAME = 14;
    private const AWAYGAME = 15;

    private const SOCCER = 1;
    private const BASKETBALL = 2;
    private const AMERICAN_FOOTBALL = 3;
    private const BASEBALL = 4;
    private const TENNIS = 5;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Sport::updateOrCreate([
            'id' => self::TENNIS
        ], [
            'sport'   => 'Tennis',
            'details' => 'Tennis Sports'
        ]);
        $iconsAndSlugs = [
            self::SOCCER            => ['fa-futbol', 'soccer'],
            self::BASKETBALL        => ['fa-basketball-ball', 'basketball'],
            self::AMERICAN_FOOTBALL => ['fa-football-ball', 'american-football'],
            self::BASEBALL          => ['fa-baseball-ball', 'baseball'],
            self::TENNIS            => ['tennis-ball', 'tennis'],
        ];

        foreach ($iconsAndSlugs as $sportId => $iconsAndSlug) {
            Sport::updateOrCreate([
                'id' => $sportId
            ], [
                'icon' => $iconsAndSlug[0],
                'slug' => $iconsAndSlug[1],
            ]);
        }

        $sportOddTypeNames = [
            self::SOCCER            => [
                self::FT1X2         => ['FT 1X2', '', ''],
                self::FTHANDICAP    => ['FT HDP', 'A/1', 'A/2'],
                self::FTOVERUNDER   => ['FT O/U', 'O', 'U'],
                self::FTODDEVEN     => ['FT O/E', 'Odd', 'Even'],
                self::ONEH1X2       => ['HT 1X2', '', ''],
                self::ONEHHANDICAP  => ['HT HDP', 'A/1', 'A/2'],
                self::ONEHOVERUNDER => ['HT O/U', 'O', 'U'],
                self::HOMEGOALS     => ['HOME GOALS', '1/O', '1/U'],
                self::AWAYGOALS     => ['AWAY GOALS', '2/O', '2/U']
            ],
            self::BASKETBALL        => [
                self::MONEYLINE   => ['MONEYLINE', '1', '2'],
                self::FTHANDICAP  => ['FT HDP', 'A/1', 'A/2'],
                self::FTOVERUNDER => ['FT O/U', 'O', 'U'],
                self::HOMEGOALS   => ['HOME GOALS', '1/O', '1/U'],
                self::AWAYGOALS   => ['AWAY GOALS', '2/O', '2/U']
            ],
            self::AMERICAN_FOOTBALL => [],
            self::BASEBALL          => [
                self::MONEYLINE      => ['MONEYLINE', '1', '2'],
                self::FTHANDICAP     => ['HDP', 'A/1', 'A/2'],
                self::FTOVERUNDER    => ['O/U', 'O', 'U'],
                self::FIRSTINNINGML  => ['Moneyline (1st Inning)', '', ''],
                self::FIRSTINNINGHDP => ['HDP (1st Inning)', '', ''],
                self::FIRSTINNINGOU  => ['O/U (1st Inning)', '', '']
            ],
            self::TENNIS            => [
                self::MONEYLINE   => ['MONEYLINE', '1', '2'],
                self::FTHANDICAP  => ['HDP', 'A/P1', 'A/P2'],
                self::FTOVERUNDER => ['O/U', 'O', 'U'],
                self::HOMEGOALS   => ['HOME GAME', '1/O', '1/U'],
                self::AWAYGOALS   => ['AWAY GAME', '2/O', '2/U']
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
            self::AMERICAN_FOOTBALL,
            self::BASEBALL,
            self::TENNIS,
        ]);
    }
}
