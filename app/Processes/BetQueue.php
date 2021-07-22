<?php

namespace App\Processes;

use App\Exceptions\NotFoundException;
use App\Jobs\KafkaPush;
use App\User;
use App\Models\{Order, OrderLogs, SystemConfiguration, ProviderAccount, ProviderAccountOrder, RetryType};
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Swoole\Http\Server;
use Swoole\Process;
use App\Facades\SwooleHandler;
use Exception;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use App\Facades\WalletFacade;

class BetQueue implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            if ($swoole->data2SwtTable->exist('data2Swt')) {
                while (!self::$quit) {
                    $betStack      = Redis::lpop('ml-queue');
                    $maxRetryCount = SystemConfiguration::getSystemConfigurationValue('RETRY_COUNT');
                    $retryExpiry   = SystemConfiguration::getSystemConfigurationValue('RETRY_EXPIRY');

                    if (!empty($betStack)) {
                        $walletToken = SwooleHandler::getValue('walletClientsTable', 'ml-users')['token'];
                        $bet         = json_decode($betStack, true);

                        try {
                            $providerToken     = SwooleHandler::getValue('walletClientsTable', trim(strtolower($bet['alias'])) . '-users')['token'];
                            $user              = User::find($bet['user_id']);
                            $prevAccount       = [];
                            $blockedLinesParam = [
                                'event_id'    => $bet['event_id'],
                                'odd_type_id' => $bet['odd_type_id'],
                                'points'      => $bet['odd_label'],
                            ];

                            if (($bet['retry_type_id']) && (RetryType::getTypeById($bet['retry_type_id']) == "auto-new-account")) {
                                $prevAccount = OrderLogs::where('order_id', $bet['id'])
                                    ->where('status', 'FAILED')
                                    ->pluck('provider_account_id')
                                    ->toArray();
                            }

                            if (($bet['retry_type_id']) && (RetryType::getTypeById($bet['retry_type_id']) == "auto-same-account")) {
                                $providerAccount = ProviderAccount::find($bet['provider_account_id']);
                            } else {
                                $providerAccount = ProviderAccount::getBettingAccount($bet['provider_id'], $bet['actual_stake'], $user->is_vip, $bet['event_id'], $bet['odd_type_id'], $bet['market_flag'], $providerToken, $blockedLinesParam, $prevAccount);
                            }

                            if (empty($providerAccount)) {
                                throw new NotFoundException(trans('game.bet.errors.no_bookmaker'));
                            }

                            ProviderAccount::where('id', $providerAccount->id)->update([
                                'updated_at' => Carbon::now()
                            ]);

                            $orderSWTKey = 'orderId:' . $bet['id'];
                            SwooleHandler::setColumnValue('ordersTable', $orderSWTKey, 'username', $providerAccount->username);

                            DB::beginTransaction();

                            Order::where('id', $bet['id'])->update([
                                'status'              => 'PENDING',
                                'provider_account_id' => $providerAccount->id,
                                'provider_id'         => $providerAccount->provider_id,
                                'reason'              => ''
                            ]);

                            $orderLog  = OrderLogs::where('order_id', $bet['id'])->orderBy('id', 'DESC')->first();
                            $orderLogs = OrderLogs::create([
                                'user_id'             => $bet['user_id'],
                                'provider_id'         => $providerAccount->provider_id,
                                'sport_id'            => $bet['sport_id'],
                                'bet_id'              => $bet['bet_id'],
                                'bet_selection'       => $bet['bet_selection'],
                                'status'              => 'PENDING',
                                'settled_date'        => null,
                                'reason'              => '',
                                'profit_loss'         => 0,
                                'order_id'            => $bet['id'],
                                'provider_account_id' => $providerAccount->id
                            ]);

                            $providerAccountOrder = ProviderAccountOrder::where('order_log_id', $orderLog->id)->orderBy('id', 'DESC')->first();

                            ProviderAccountOrder::create([
                                'order_log_id'       => $orderLogs->id,
                                'exchange_rate_id'   => $providerAccountOrder->exchange_rate_id,
                                'actual_stake'       => $providerAccountOrder->actual_stake,
                                'actual_to_win'      => $providerAccountOrder->actual_to_win,
                                'actual_profit_loss' => $providerAccountOrder->actual_profit_loss,
                                'exchange_rate'      => $providerAccountOrder->exchange_rate,
                            ]);

                            DB::commit();

                            $requestId = Str::uuid() . "-" . $bet['id'];
                            $requestTs = getMilliseconds();
                            $payload   = [
                                'request_uid' => $requestId,
                                'request_ts'  => $requestTs,
                                'sub_command' => 'place',
                                'command'     => 'bet'
                            ];

                            $payload['data'] = [
                                'provider'  => strtolower($bet['alias']),
                                'sport'     => $bet['sport_id'],
                                'stake'     => $bet['actual_stake'],
                                'odds'      => $bet['odds'],
                                'market_id' => $bet['market_id'],
                                'event_id'  => $bet['event_id'],
                                'score'     => $bet['score_on_bet'],
                                'username'  => $providerAccount->username
                            ];

                            $betRetryPush = app('BetRetryPush');
                            Task::deliver($betRetryPush->init($bet, $payload, $requestId));
                        } catch (NotFoundException $e) {
                            $maxRetryCount = SystemConfiguration::getSystemConfigurationValue('RETRY_COUNT');
                            $retryExpiry   = SystemConfiguration::getSystemConfigurationValue('RETRY_EXPIRY');
                            $now           = Carbon::now();

                            $bet['retry_count'] += 1;

                            if ($bet['retry_count'] < $maxRetryCount->value && $now->diffInSeconds($bet['created_at']) <= $retryExpiry->value) {
                                Order::where('id', $bet['id'])->update([
                                    'retry_count' => $bet['retry_count']
                                ]);

                                retryCacheToRedis($bet);
                            } else {
                                self::failBet($walletToken, $bet, trans('game.bet.errors.no_bookmaker'));
                            }
                        } catch (QueryException $e) {
                            DB::rollback();
                        } catch (Exception $e) {
                            throw $e;
                        }
                    } else {
                        usleep(50000);
                    }
                }
            }
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "BetQueue",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage() . " | " . $e->getFile(),
                "module"      => "PRODUCE_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_process', 'error', $toLogs);
        }
    }

    private static function failBet($walletToken, $bet, $reason)
    {
        if ($bet) {
            $order = Order::updateOrCreate([
                'id' => $bet['id']
            ], [
                'bet_id'                    => '',
                'reason'                    => $reason,
                'status'                    => 'FAILED',
                'provider_error_message_id' => null
            ]);
            
            $user         = User::find($order->user_id);
            $currencyCode = $user->currency()->first()->code;
            $reason       = "[RETURN_STAKE][BET FAILED/CANCELLED] - transaction for order id " . $order->id;
            $userBalance  = WalletFacade::addBalance($walletToken, $user->uuid, trim(strtoupper($currencyCode)), $order->stake, $reason);
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
