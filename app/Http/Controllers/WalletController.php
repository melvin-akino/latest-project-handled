<?php

namespace App\Http\Controllers;
#use App\Exceptions\ServerException;
use App\Models\Currency;
use App\Models\UserWallet;
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
       
        $wallet = UserWallet::where('user_id',auth()->user()->id )->first();
        $balance = $wallet->balance;
        $profit_loss = $wallet->Order()->sum('profit_loss');
        $orders  = $wallet->Order()->where('settled_date','')->sum('stake');
        return response()->json([
            'status'    => true,
            'status_code' => 200,
            'data' => [
                'currency_symbol' => Currency::find(auth()->user()->currency_id)->symbol,
                'credit'    => $balance,
                'profit_loss' => $profit_loss,
                'orders' =>  $order,
                
                ],
        ], 200);

         
        
    }
}
