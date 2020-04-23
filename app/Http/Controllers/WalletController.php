<?php

namespace App\Http\Controllers;
#use App\Exceptions\ServerException;
use App\Models\{Currency, UserWallet};
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Fetch Authenticated User's Wallet Information
     *
     * @return json
     */
    public function userWallet() {
    
        $balance      =  0.00;
        $profit_loss  =  0.00;
        $orders       =  0.00;

        $wallet = UserWallet::where('user_id',auth()->user()->id )->first();
        if ($wallet) {
            $balance     = $wallet->balance;
            $profit_loss = $wallet->Order()->sum('profit_loss');
            $orders      = $wallet->Order()->where('settled_date', '')->orWhereNull('settled_date')->whereIn('status', ['PENDING', 'SUCCESS'])->sum('stake');
        }
        return response()->json([
            'status'      => true,
            'status_code' => 200,
            'data'        => [
                'currency_symbol' => Currency::find(auth()->user()->currency_id)->symbol,
                'credit'          => (float) $balance,
                'profit_loss'     => (float) $profit_loss,
                'orders'          => (float) $orders,
                
            ],
        ]); 

    }
}
