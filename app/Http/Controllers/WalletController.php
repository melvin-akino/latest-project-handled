<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Fetch Authenticated User's Wallet Information
     *
     * @return json
     */
    public function userWallet()
    {
        return response()->json([
            'status'      => true,
            'status_code' => 200,
            'data'        => [
                'currency'    => Currency::find(auth()->user()->currency_id)->symbol,
                'credit'      => 800,
                'profit_loss' => 0,
                'orders'      => 200,
            ],
        ]);
    }
}
