<?php

namespace App\Processes;

use App\Jobs\KafkaPush;
use App\User;
use App\Models\{Order, OrderLogs, SystemConfiguration, ProviderAccount, RetryType};
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Swoole\Http\Server;
use Swoole\Process;
use App\Facades\SwooleHandler;
use Exception;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

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
                    // var_dump($betStack);
                    $maxRetryCount = SystemConfiguration::getSystemConfigurationValue('RETRY_COUNT');
                    $retryExpiry   = SystemConfiguration::getSystemConfigurationValue('RETRY_EXPIRY');

                    if (!empty($betStack)) {
                        $walletToken = SwooleHandler::getValue('walletClientsTable', 'ml-users')['token'];
                        $bet         = json_decode($betStack, true);

                        try {
                            if ($bet['retry_count'] < $maxRetryCount->value) {
                                $now = Carbon::now();

                                if ($now->diffInSeconds($bet['created_at']) <= $retryExpiry['value']) {
                                    $providerToken     = SwooleHandler::getValue('walletClientsTable', trim(strtolower($bet['alias'])) . '-users')['token'];
                                    $user              = User::find($bet['user_id']);
                                    $retryType         = null;
                                    $blockedLinesParam = [
                                        'event_id'    => $bet['event_id'],
                                        'odd_type_id' => $bet['odd_type_id'],
                                        'points'      => $bet['odd_label'],
                                    ];

                                    $providerAccount = ProviderAccount::getBettingAccount($bet['provider_id'], $bet['actual_stake'], $user->is_vip, $bet['event_id'], $bet['odd_type_id'], $bet['market_flag'], $providerToken, $blockedLinesParam);

                                    if (empty($providerAccount)) {
                                        throw new NotFoundException($bet);
                                    }

                                    var_dump($providerAccount);
                                    $orderSWTKey = 'orderId:' . $bet['id'];
                                    SwooleHandler::setColumnValue('ordersTable', $orderSWTKey, 'username', $providerAccount->username);

                                    DB::beginTransaction();

                                    Order::where('id', $bet['id'])->update([
                                        'status'              => 'PENDING',
                                        'provider_account_id' => $providerAccount->id,
                                        'provider_id'         => $providerAccount->provider_id,
                                        'reason'              => 'Trying to place bet'
                                    ]);

                                    OrderLogs::create([
                                        'user_id'             => $bet['user_id'],
                                        'provider_id'         => $providerAccount->provider_id,
                                        'sport_id'            => $bet['sport_id'],
                                        'bet_id'              => $bet['bet_id'],
                                        'bet_selection'       => $bet['bet_selection'],
                                        'status'              => 'PENDING',
                                        'settled_date'        => null,
                                        'reason'              => 'Trying to place bet',
                                        'profit_loss'         => 0,
                                        'order_id'            => $bet['id'],
                                        'provider_account_id' => $providerAccount->id
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
                                        'provider'  => $bet['alias'],
                                        'sport'     => $bet['sport_id'],
                                        'stake'     => $bet['actual_stake'],
                                        'odds'      => $bet['odds'],
                                        'market_id' => $bet['market_id'],
                                        'event_id'  => $bet['event_id'],
                                        'score'     => $bet['score_on_bet'],
                                        'username'  => $providerAccount->username
                                    ];

                                    KafkaPush::dispatch(
                                        strtolower($bet['alias']) . env('KAFKA_SCRAPE_ORDER_REQUEST_POSTFIX', '_bet_req'),
                                        $payload,
                                        $requestId
                                    );
                                } else {
                                    // Expired retry
                                    continue;
                                }
                            } else {
                                // Max count retry, should fail the bet data
                                self::failBet($walletToken, $bet, $bet['reason']);
                                continue;
                            }
                        } catch (NotFoundException $bet) {
                            retryCacheToRedis($bet);
                        } catch (QueryException $e) {
                            DB::rollback();
                        } catch (Exception $e) {
                            throw $e;
                        }
                    } else {
                        usleep(5000);
                    }
                }
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getLine());
            $toLogs = [
                "class"       => "BetQueue",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "PRODUCE_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_process', 'error', $toLogs);
        }
    }

    private function failBet($walletToken, $bet, $reason)
    {
        if ($bet) {
            $order = Order::updateOrCreate([
                'id' => $bet['id']
            ], [
                'bet_id'                    => null,
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
