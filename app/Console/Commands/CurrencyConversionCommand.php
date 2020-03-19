<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use App\Models\{Currency, ExchangeRate};
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use DateTime;

class CurrencyConversionCommand extends Command
{
    protected $signature = 'currency:convert';

    protected $description = 'Get Conversion';

    private $client;

    const BASE_CURRENCY_ID = 1;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    public function handle()
    {
        $executionTime = '14:34:10';
        $oneTimeRun = 0;

        while(true) {
            $currentTime = (new DateTime())->format('H:i:s');

            if ($currentTime == $executionTime) {
                $conversionApi = "https://api.exchangeratesapi.io/latest?base=%s&symbols=%s";
                $currencies    = DB::table('currency AS cfrom', '!=', DB::raw('0'))
                    ->join('currency AS cto', function ($join) {
                        $join->on('cfrom.id', '=', 'cto.id');
                        $join->orOn('cfrom.id', '!=', 'cto.id');
                    })
                    ->orderBy('cfrom.id', 'asc')
                    ->orderBy('cto.id', 'asc')
                    ->get([
                        'cfrom.id AS from_id',
                        'cfrom.code AS from_code',
                        'cto.id AS to_id',
                        'cto.code AS to_code',
                    ]);

                foreach ($currencies as $currency) {
                    $api      = sprintf($conversionApi, trim($currency->from_code), trim($currency->to_code));
                    $response = $this->client->request('GET', $api);

                    if ($response->getStatusCode() == 200) {
                        $objectResponse = json_decode($response->getBody()->getContents());

                        ExchangeRate::updateOrCreate([
                            'from_currency_id' => $currency->from_id,
                            'to_currency_id'   => $currency->to_id,
                        ], [
                            'default_amount' => 1,
                            'exchange_rate'  => $objectResponse->rates->{trim($currency->to_code)}
                        ]);
                    }
                }
            }
        }
    }
}
