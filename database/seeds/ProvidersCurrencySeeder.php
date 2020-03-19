<?php

use App\Models\Currency;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProvidersCurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $array = [
            [
                'alias'       => "HG",
                'currency_id' => Currency::getIdByCode('CNY'),
            ],
            [
                'alias'       => "ISN",
                'currency_id' => Currency::getIdByCode('USD'),
            ],
            [
                'alias'       => "PIN",
                'currency_id' => Currency::getIdByCode('USD'),
            ],
        ];

        foreach ($array AS $row) {
            Provider::where('alias', $row['alias'])
                ->update([ 'currency_id' => $row['currency_id'] ]);
        }
    }
}
