<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\ExchangeRate;
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
        $executionTime = '00:00:00';
        while(true) {
            $currentTime = (new DateTime())->format('H:i:s');
            if ($currentTime == $executionTime) {
                $baseCurrency = Currency::find(self::BASE_CURRENCY_ID);

                $currencies = Currency::all();

                $conversionApi = "https://api.exchangeratesapi.io/latest?base=%s&symbols=%s";
                foreach ($currencies as $currency) {
                    $exchangeCurrency = Currency::find($currency->id);
                    $api = sprintf($conversionApi, trim($baseCurrency->code), trim($exchangeCurrency->code));
                    $response = $this->client->request('GET', $api);

                    if ($response->getStatusCode() == 200) {
                        $objectResponse = json_decode($response->getBody()->getContents());
                        ExchangeRate::updateOrCreate([
                            'from_currency_id' => $baseCurrency->id,
                            'to_currency_id' => $exchangeCurrency->id,
                        ], [
                            'default_amount' => 1,
                            'exchange_rate' => $objectResponse->rates->{trim($exchangeCurrency->code)}
                        ]);
                    }
                }
            }
        }


    }
}
