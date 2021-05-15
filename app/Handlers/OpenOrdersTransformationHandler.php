<?php

namespace App\Handlers;

use App\Exceptions\{BadRequestException, NotFoundException};
use App\Models\{
    Order,
    ExchangeRate,
    UserWallet,
    Source,
    OrderLogs,
    OrderTransaction,
    ProviderAccountOrder,
    OddType,
    WalletLedger,
    ProviderAccount,
    Currency,
    Provider,
    ProviderBet,
    ProviderBetLog,
    ProviderBetTransaction,
    BetWalletTransaction
};
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\{DB, Log};
use App\Facades\WalletFacade;

class OpenOrdersTransformationHandler
{
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function init($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $hasError = false;
        $errMsg   = "";

        try {
            DB::beginTransaction();

            $swoole      = app('swoole');
            $ordersTable = $swoole->ordersTable;
            $providers   = $swoole->providersTable;
            $openOrders  = $this->data->data;
            $colMinusOne = OddType::whereIn('type', ['1X2', 'HT 1X2', 'OE'])->pluck('id')->toArray();

            foreach ($ordersTable as $_key => $orderTable) {
                if (!in_array(strtoupper($orderTable['status']), ['SUCCESS', 'PENDING'])) {
                    continue;
                }

                $orderId        = substr($_key, strlen('orderId:'));
                $expiry         = $orderTable['orderExpiry'];
                $orderData      = ProviderBet::find($orderId);
                $userWallet     = UserWallet::where('user_id', $orderTable['user_id'])->first();
                $sourceId       = Source::where('source_name', 'LIKE', 'PLACE_BET')->first();
                $betLogId       = 0;
                $walletLedgerId = 0;
                $credit         = 0;

                if (!empty($openOrders)) {
                    foreach ($openOrders as $order) {
                        $betId            = $order->bet_id;
                        $providerCurrency = $providers->get('providerAlias:' . $order->provider)['currency_id'];
                        $exchangeRate     = ExchangeRate::where('from_currency_id', $providerCurrency)->where('to_currency_id', 1)->first();
                        $stake            = $order->stake * $exchangeRate->exchange_rate;

                        if ($orderTable['bet_id'] == $betId) {
                            if (strtoupper($order->status) != strtoupper($orderData->status)) {
                                $ordersTable[$_key]['status'] = strtoupper($order->status);

                                $providerBet = ProviderBet::where('id', $orderId);
                                $providerBet->update([
                                    'provider_account_id' => ProviderAccount::getUsernameId($orderTable['username']),
                                    'status'              => strtoupper($order->status),
                                    'odds'                => $order->odds,
                                    'reason'              => $order->reason,
                                    'to_win'              => !in_array($orderData->odd_type_id, $colMinusOne) ? $orderData->stake * $order->odds : $orderData->stake * ($order->odds - 1),
                                    'bet_id'              => $betId
                                ]);

                                $betLog = ProviderBetLog::create([
                                    'provider_bet_id' => $providerBet->id,
                                    'status'          => strtoupper($order->status),
                                ]);

                                $toLogs = [
                                    "module"            => "BET_INFO",
                                    "status"            => $order->status,
                                    "ml_bet_identifier" => $orderData->ml_bet_identifier,
                                    "bet_id"            => $betId,
                                    "username"          => $orderTable['username']
                                ];
                                monitorLog('monitor_bet_info', 'info', $toLogs);

                                $betLogId = $betLog->id;

                                ProviderBetTransaction::create([
                                    'order_log_id'       => $betLogId,
                                    'exchange_rate_id'   => $exchangeRate->id,
                                    'actual_stake'       => $stake,
                                    'actual_to_win'      => !in_array($orderData->odd_type_id, $colMinusOne) ? $stake * $order->odds : $stake * ($order->odds - 1),
                                    'actual_profit_loss' => 0.00,
                                    'exchange_rate'      => $exchangeRate->exchange_rate,
                                    'punter_percentage'  => Provider::find($order->provider_id)->punter_percentage,
                                ]);

                                if (in_array(strtoupper($order->status), [
                                    'FAILED',
                                    'CANCELLED',
                                ])) {
                                    $credit             = $orderData->stake;
                                    $walletClientsTable = app('swoole')->walletClientsTable;
                                    $userToken          = $walletClientsTable['ml-users']['token'];
                                    $user               = User::find($orderTable['user_id']);
                                    $currency           = Currency::find($providerCurrency);
                                    $creditReason       = "[RETURN_STAKE][BET FAILED/CANCELLED] - transaction for order id " . $orderId;
                                    $addBalance         = WalletFacade::addBalance($userToken, $user->uuid, $currency->code, $credit, $creditReason);

                                    if ($addBalance->status) {
                                        $walletLedgerId = $addBalance->data->id;
                                    } else {
                                        throw new Exception('Wallet Credit Failed');
                                    }
                                }

                                orderStatus($orderTable['user_id'], $orderId, $expiry, $orderTable['created_at']);

                                if (in_array(strtoupper($order->status), [
                                    'FAILED',
                                    'CANCELLED',
                                ])) {
                                    $ordersTable->del($_key);
                                }
                            }
                        }

                        BetWalletTransaction::create([
                            'provider_bet_log_id' => $betLogId,
                            'user_id'             => $orderTable['user_id'],
                            'source_id'           => $sourceId->id,
                            'currency_id'         => $providerCurrency,
                            'wallet_ledger_id'    => $walletLedgerId,
                            'provider_account_id' => ProviderAccount::getUsernameId($orderTable['username']),
                            'reason'              => $order->reason,
                            'amount'              => $credit
                        ]);
                    }
                }
            }

            Log::info("Open Orders - Processed");

            DB::commit();
        } catch (BadRequestException $e) {
            $hasError = true;
            $errCode  = 400;
            $errMsg = "Line " . $e->getLine() . " | " . $e->getMessage();

            DB::rollBack();
        } catch (NotFoundException $e) {
            $hasError = true;
            $errCode  = 404;
            $errMsg = "Line " . $e->getLine() . " | " . $e->getMessage();

            DB::rollBack();
        } catch (Exception $e) {
            $hasError = true;
            $errCode  = 500;
            $errMsg = "Line " . $e->getLine() . " | " . $e->getMessage();

            DB::rollBack();
        }

        if ($hasError) {
            $toLogs = [
                "class"       => "OpenOrdersTransformationHandler",
                "message"     => $errMsg,
                "module"      => "BET_INFO_ERROR",
                "status_code" => $errCode,
            ];
            monitorLog('monitor_bet_info', 'error', $toLogs);
        }
    }
}
