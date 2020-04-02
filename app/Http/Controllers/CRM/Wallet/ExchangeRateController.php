<?php

namespace App\Http\Controllers\CRM\Wallet;


use App\Http\Controllers\Controller;

use App\Http\Requests\CRM\ExchangeRateRequest;
use App\Models\{Currency, ExchangeRate};
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function dataTable(Request $request)
    {
        return dataTable($request, ExchangeRate::with('currency_from', 'currency_to'));
    }

    public function index()
    {
       
        $data['wallet_menu']        = true;
        $data['exchange_rate_menu'] = true;
        $data['page_title']         = 'Exchange Rate';
        $data['page_description']   = 'Exchange Rate';
        $data['in_app_currencies']  = Currency::all();
        $data['default_amount']     = ExchangeRate::$default_amount;
     
        return view('CRM.wallet.exchange_rate.index')->with($data);
    }

    public function store(ExchangeRateRequest $request)
    {

        ExchangeRate::create([
            'from_currency_id' => $request->to_currency,
            'to_currency_id'   => $request->from_currency,
            'default_amount'   => ExchangeRate::$default_amount,
            'exchange_rate'    => ExchangeRate::$default_amount / $request->exchange_rate
        ]);

        return response()->json([
            config('response.status') => config('response.type.success')
        ], 201);
    }

    public function update(ExchangeRateRequest $request, ExchangeRate $exchange_rate)
    {
        
        $exchange_rate->update([
            'exchange_rate' => ExchangeRate::$default_amount / $request->exchange_rate
        ]);
        $exchange_rate->save();

        return response()->json([
            config('response.status') => config('response.type.success')
        ]);
    }
}