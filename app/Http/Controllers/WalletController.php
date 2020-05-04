<?php

namespace App\Http\Controllers;

use App\Models\{Currency, UserWallet};
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    /**
     * Fetch Authenticated User's Wallet Information
     *
     * @return json
     */
    public function userWallet()
    {

        try {
            $balance     = 0.00;
            $profit_loss = 0.00;
            $orders      = 0.00;

            $wallet = UserWallet::where('user_id', auth()->user()->id)->first();
            if ($wallet) {
                $balance     = $wallet->balance;
                $profit_loss = $wallet->Order()->sum('profit_loss');
                $orders      = $wallet->Order()->whereIn('status', ['PENDING', 'SUCCESS'])->sum('stake');
            }
            return response()->json([
                'status'      => true,
                'status_code' => 200,
                'data'        => [
                    'currency_symbol' => Currency::find(auth()->user()->currency_id)->symbol,
                    'credit'          => (float)$balance,
                    'profit_loss'     => (float)$profit_loss,
                    'orders'          => (float)$orders,

                ],
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }
}
