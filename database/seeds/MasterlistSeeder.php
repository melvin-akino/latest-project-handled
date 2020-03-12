<?php

use App\Models\{MasterLeague, MasterLeagueLink, MasterTeam, MasterTeamLink};
use Illuminate\Database\Seeder;

class MasterlistSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $masterLeagues = [
            'Indonesia Liga 1',
            'International Friendly Women',
            'Australia Victoria National Premier League',
            'Mexico Ascenso MX',
            'Israel Liga Bet',
            'Australia Victoria National Premier League U20',
            'Australia A League',
            'Israel Liga Bet',
            'Club Friendly',
            'Turkey Super League',
            'Austria 2 Liga',
            'English League 1',
            'Russia Premier League',
            'Italy Serie C',
            'ATP Challenger - Nur-Sultan',
            'South Australia Reserve National Premier League',
            'India I League',
            'Chile - Primera Division',
            'Argentina Superliga',
            'Jamaica Premier League',
            'Colombia Primera B',
            'NBA',
            'Turkey TFF First League',
            'Spain Segunda Division',
            'AFC Cup',
            'Ireland Premier Division',
            'Copa Libertadores',
            'CONCACAF Champions League',
            'Guatemala Liga Nacional',
            'USL Championship',
            'Argentina Cup',
            'Mexico Cup',
            'UEFA Europa League',
            'Fantasy Matches',
            'Denmark 1st Division'
        ];

        $masterTeams = [
            'Armenia (W)',
            'Lithuania (W)',
            'Melbourne Knights',
            'Hume City',
            'Persita Tangerang',
            'PSM Makassar',
            'Cimarrones De Sonora',
            'Correcaminos UAT',
            'Hapoel Qalansawe',
            'Hapoel Mahane Yehuda',
            'Bentleigh Greens U20',
            'St Albans Saints U20',
            'Oakleigh Cannons U20',
            'Altona Magic U20',
            'Melbourne Knights',
            'Hume City',
            'Port Melbourne Sharks SC',
            'South Melbourne',
            'Bentleigh Greens',
            'St Albans Saints',
            'Brisbane Roar',
            'Western Sydney Wanderers',
            'Hapoel Yeruham',
            'Bnei Eilat',
            'Stromsgodset',
            'Valerenga',
            'PS Barito Putera',
            'Bali United',
            'Beitar Ironi Kiryat Gat',
            'Maccabi Ironi Kiryat Malakhi',
            'Sandnes Ulf',
            'Asane Fotball',
            'Ironi Beit Dagan',
            'Hapoel Kfar Bara',
            'Adelaide United',
            'Western United',
            'Yeni Malatyaspor',
            'Konyaspor',
            'Honka Espoo',
            'Viking FK',
            'Eastern Lions SC',
            'Dandenong Thunder',
            'Juniors OO',
            'Kapfenberger SV',
            'Blackpool',
            'Tranmere Rovers',
            'Rostov',
            'CSKA Moscow',
            'Madura United',
            'Persiraja Banda Aceh',
            'Cavese',
            'Potenza',
            'Tomas Machac (CZE)',
            'Aslan Karatsev (RUS)',
            'Adelaide Blue Eagles (R)',
            'Adelaide Raiders SC (R)',
            'Real Kashmir',
            'East Bengal',
            'Union La Calera',
            'Coquimbo',
            'Estudiantes La Plata',
            'Racing Club',
            'Arnett Gardens',
            'Waterhouse',
            'Boca Juniors De Cali',
            'Deportes Quindio',
            'Utah Jazz',
            'Toronto Raptors',
            'Denver Nuggets',
            'Milwaukee Bucks',
            'Boluspor',
            'Adanaspor',
            'Altay SK',
            'Hatayspor',
            'Buyuksehir Belediye Erzurumspor',
            'Fatih Karagumruk',
            'Racing Santander',
            'CD Lugo',
            'Sarpsborg 08 FF',
            'Kongsvinger IL',
            'Istiklol',
            'FK Khujand',
            'Dundalk',
            'Saint Patricks Athletic',
            'Shamrock Rovers',
            'Finn Harps',
            'Flamengo RJ',
            'Barcelona SC',
            'Club America',
            'Atlanta United',
            'Club Xelaju MC',
            'CSD Comunicaciones',
            'Tacoma Defiance',
            'San Diego Loyal',
            'Argentinos Juniors',
            'Canuelas',
            'CF Monterrey',
            'Juarez',
            'Osmanlispor FK',
            'Eskisehirspor',
            'Istanbul Basaksehir',
            'Copenhagen',
            'Lask Linz',
            'Manchester United',
            'Eintracht Frankfurt',
            'Basel',
            'HB Koge',
            'Hvidovre IF'
        ];

        foreach ($masterLeagues as $league) {
            $masterLeague = MasterLeague::withTrashed()->updateOrCreate([
                'master_league_name' => $league,
                'sport_id'           => 1
            ], [
                'deleted_at' => null
            ]);

            MasterLeagueLink::withTrashed()->updateOrCreate([
                'master_league_id' => $masterLeague->id,
                'sport_id'         => 1,
                'provider_id'      => 1,
                'league_name'      => $league
            ], [
                'deleted_at' => null
            ]);
        }

        foreach ($masterTeams as $team) {
            $masterTeam = MasterTeam::withTrashed()->updateOrCreate([
                'sport_id'         => 1,
                'master_team_name' => $team,
            ], [
                'deleted_at' => null
            ]);

            MasterTeamLink::withTrashed()->updateOrCreate([
                'sport_id'       => 1,
                'team_name'      => $team,
                'provider_id'    => 1,
                'master_team_id' => $masterTeam->id
            ], [
                'deleted_at' => null
            ]);
        }
    }
}
