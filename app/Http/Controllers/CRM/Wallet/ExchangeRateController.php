<?php

namespace App\Http\Controllers\CRM\Wallet;


use App\Http\Controllers\Controller;


use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function dataTable(Request $request)
    {
        return dataTable($request, ExchangeRate::with('currency_from', 'currency_to'));
    }

    public function index()
    {
        $data['wallet_menu'] = true;
        $data['exchange_rate_menu'] = true;
        $data['page_title'] = 'Exchange Rate';
        // $data['in_app_currencies'] = Currency::where('app_currency', true)->get();
        $data['in_app_currencies'] = Currency::where('app_currency', false)->get();
        return view('CRM.wallet.exchange_rate.index')->with($data);
    }

    public function store(ExchangeRateRequest $request)
    {
        ExchangeRate::create($request->all());

        ExchangeRate::create([
            'from_currency' => $request->to_currency,
            'to_currency' => $request->from_currency,
            'default_amount' => $request->default_amount,
            'exchange_rate' => $request->default_amount / $request->exchange_rate
        ]);

        return response()->json([
            config('response.status') => config('response.type.success')
        ], 201);
    }

    public function update(ExchangeRateRequest $request, ExchangeRate $exchange_rate)
    {
        $reversed_exchange_rate = ExchangeRate::where('from_currency', $exchange_rate->to_currency)
            ->where('to_currency', $exchange_rate->from_currency)
            ->first();

        if (is_null($reversed_exchange_rate)) {
            //todo
        }

        $reversed_exchange_rate->update([
            'exchange_rate' => ExchangeRate::$default_amount / $request->exchange_rate
        ]);
        $reversed_exchange_rate->save();

        $exchange_rate->update($request->all());
        $exchange_rate->save();

        return response()->json([
            config('response.status') => config('response.type.success')
        ]);
    }
}
