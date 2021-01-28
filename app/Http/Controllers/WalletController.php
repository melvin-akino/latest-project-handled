<?php

namespace App\Http\Controllers;

use App\Models\{Currency, UserWallet, Order};
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Facades\WalletFacade;

class WalletController extends Controller
{
    /**
     * Fetch Authenticated User's Wallet Information
     *
     * @return json
     */
    public function userWallet(Request $request, WalletService $walletService)
    {

        try {
            $user           = auth()->user();
            $userId         = $user->id;
            $currencyId     = $user->currency_id;
            $currency       = Currency::find($currencyId);
            $balance        = 0.00;
            $profit_loss    = 0.00;
            $orders         = 0.00;

            $token = app('swoole')->walletClientsTable['ml-users']['token'];
            $getBalance = $walletService->getBalance($token, $user->uuid, trim(strtoupper($currency->code)));

            if ($getBalance->status) {
                $balance     = $getBalance->data->balance;
                $profit_loss = Order::where('user_id', $userId)->sum('profit_loss');
                $orders      = Order::where('user_id', $userId)->whereIn('status', ['PENDING', 'SUCCESS'])->sum('stake');

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
            } else {
                if ($getBalance->status_code == 400) {  
                    Log::error($getBalance->errors);
                } else {  
                    Log::error($getBalance->error);
                }
                return response()->json([
                    'status'      => false,
                    'status_code' => 500,
                    'message'     => trans('generic.internal-server-error')
                ], 500);
            }
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
