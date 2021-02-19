<?php

namespace App\Http\Controllers;

use App\Models\{Currency, Timezones, UserConfiguration, Order};
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Facades\WalletFacade;
use Carbon\Carbon;

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
            $user          = auth()->user();
            $userId        = $user->id;
            $currencyId    = $user->currency_id;
            $currency      = Currency::find($currencyId);
            $userTz        = "Etc/UTC";
            $getUserConfig = UserConfiguration::getUserConfig($userId)
                ->where('type', 'timezone')
                ->first();

            if (!is_null($getUserConfig)) {
                $userTz = Timezones::find($getUserConfig->value)->name;
            }

            $balance     = 0.00;
            $profit_loss = 0.00;
            $orders      = 0.00;
            $today       = Carbon::createFromFormat("Y-m-d", Carbon::now()->format("Y-m-d"), 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d");
            $yesterday   = Carbon::createFromFormat("Y-m-d", Carbon::now()->subDay(1)->format("Y-m-d"), 'Etc/UTC')->setTimezone($userTz)->format("Y-m-d");
            $token       = app('swoole')->walletClientsTable['ml-users']['token'];
            $getBalance  = $walletService->getBalance($token, $user->uuid, trim(strtoupper($currency->code)));

            if ($getBalance->status) {
                $balance     = $getBalance->data->balance;
                $profit_loss = Order::where('user_id', $userId)->sum('profit_loss');
                $orders      = Order::where('user_id', $userId)->whereIn('status', ['PENDING', 'SUCCESS'])->sum('stake');
                $todayPL     = Order::where('user_id', $userId)->whereBetween(DB::raw("created_at AT TIME ZONE 'UTC' AT TIME ZONE '$userTz'"), [$today . " 00:00:00", $today . " 23:59:59"])->sum('profit_loss');
                $ystrdyPL    = Order::where('user_id', $userId)->whereBetween(DB::raw("created_at AT TIME ZONE 'UTC' AT TIME ZONE '$userTz'"), [$yesterday . " 00:00:00", $yesterday . " 23:59:59"])->sum('profit_loss');

                return response()->json([
                    'status'      => true,
                    'status_code' => 200,
                    'data'        => [
                        'currency_symbol' => Currency::find(auth()->user()->currency_id)->symbol,
                        'credit'       => (float) $balance,
                        'profit_loss'  => (float) $profit_loss,
                        'orders'       => (float) $orders,
                        'today_pl'     => (float) $todayPL,
                        'yesterday_pl' => (float) $ystrdyPL,
                    ],
                ]);
            } else {
                if ($getBalance->status_code == 400) {
                    Log::error((array) $getBalance->errors);
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
            $toLogs = [
                "class"       => "WalletController",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "API_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'message'     => trans('generic.internal-server-error')
            ], 500);
        }
    }
}
