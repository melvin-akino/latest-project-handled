<?php

namespace App\Http\Controllers\CRM\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\CurrencyRequest;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
   
    public function dataTable(Request $request)
    {
        return dataTable($request, Currency::query());
    }

    public function index()
    {
        $data['wallet_menu']      = true;
        $data['currency_menu']    = true;
        $data['page_title']       = 'Currency';
        $data['page_description'] = 'Wallet';

       return view('CRM.wallet.currency.index')->with($data);
    }

    public function store(CurrencyRequest $request)
    {
            
        Currency::create([
            'name'   => $request->currency_name,
            'symbol' => $request->currency_symbol,
            'code'   => $request->currency_code
           
        ]);

        return response()->json([
            config('response.status') => config('response.type.success')
        ], 201);
    }

    public function update(CurrencyRequest $request, Currency $currency)
    {
              
        $currency->update([
            'name'   => $request->currency_name,
            'symbol' => $request->currency_symbol,
            'code'   => $request->currency_code,
           
        ]);
        $currency->save();

        return response()->json([
            config('response.status') => config('response.type.success')
        ]);
    }

    public function setDefaultRegistration($boolA, $boolB)
    {
        $default = $boolA === '1' ? 1 : 0;
        
        return $this->setAppCurrency($boolB) ? 0 : $default;
    }

    public function setAppCurrency($bool)
    {
        return $bool === '1' ? 1 : 0;
    }
}
