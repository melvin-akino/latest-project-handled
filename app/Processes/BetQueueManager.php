<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\{DB, Log, Redis};
use App\Models\UserBet;
use Swoole\Http\Server;
use Swoole\{Process, Coroutine};
use Exception;

class BetQueueManager implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        $minMaxData = $swoole->minMaxDataTable;
        $minMaxRequest = $swoole->minMaxRequestsTable;

        $walletToken = SwooleHandler::getValue('walletClientsTable', 'ml-users')['token'];

        $providers = Provider::getActiveProviders();
        $colMinusOne    = OddType::whereIn('type', ['1X2', 'HT 1X2', 'OE'])->pluck('id')->toArray();

        while(true) {

            if (!SwooleHandler::exists('walletClientsTable', 'ml-users')) {
                usleep(1000000);
                continue;
            }

            try {
                DB::beginTransaction();

                $userBets = UserBet::getPending();
                if ($userBets->count() > 0) {
                    foreach ($userBets as $userBet) {
                        // Skip when User Bet Expires and decrement minmax swt counter
                        $currentTime = Carbon::now()->toDateTimeString();
                        $expireTime  = Carbon::parse($userBet->created_at)->addSeconds($userBet->order_expiry)->toDateTimeString();
                        if ($currentTime > $expireTime) {
                            foreach ($minMaxRequests as $key => $minMaxRequest) {
                                if (strpos($key, $userBet->mem_uid) !== false) {
                                    $minMaxRequest->decr(1);
                                }
                            }
                            continue;
                        }
                        
                        // Skip when there's no minmax
                        $minOdds = null;
                        $maxOdds = 0;
                        $minBet = null;
                        $maxBet = 0;
                        $worstProvider = null;
                        $bestProvider = null;
                        $marketId = null;

                        $marketProviders = explode(',', $userBet->market_providers);
                        foreach ($marketProviders as $marketProvider) {
                            $provider = Provider::find($marketProvider);
                            $minMaxKey = $userBet->mem_uid . ':' . strtolower($provider->alias);
                            if ($minMaxData->exists($minMaxKey)) {
                                
                                if (is_null($minOdds)) {
                                    $minOdds = $minMaxData[$minMaxKey]['odds'];
                                    $minBet = $minMaxData[$minMaxKey]['max'];
                                    $worstProvider = strtolower($provider->alias);
                                }

                                if ($maxOdds <= $minMaxData[$minMaxKey]['odds']) {
                                    $maxOdds = $minMaxData[$minMaxKey]['odds'];
                                    if ($maxBet < $minMaxData[$minMaxKey]['max']) {
                                        $maxBet = $minMaxData[$minMaxKey]['max'];
                                        $bestProvider = strtolower($provider->alias);
                                        $marketId = $minMaxData[$minMaxKey]['market_id'];
                                    }
                                }

                                if ($minOods > $minMaxData[$minMaxKey]['odds']) {
                                    $maxOdds = $minMaxData[$minMaxKey]['odds'];
                                    $minBet = $minMaxData[$minMaxKey]['max'];
                                    $worstProvider = strtolower($provider->alias);
                                }
                            }
                        }

                        if (is_null($worstProvider)) {
                            continue;
                        }

                        // Skip when there's an existing PENDING bet
                        $providerBetPendings = ProviderBet::getPending($userBet);
                        if ($providerBetPendings->count() > 0) {
                            continue;
                        }


                        $providerTotalBets = ProviderBet::getTotalStake($userBet);
                        if ($providerTotalBets->totalStake == $userBet->stake) {
                            UserBet::where('id', $userBet->id)->update([
                                'status' => 'PLACED'
                            ]);
                        } else {
                            $providerBetQueues = ProviderBet::getQueue($userBet);
                            if ($providerBetQueues->count() > 0) {
                                foreach ($providerBetQueues as $providerBetQueue) {
                                    // Assign new provider account to existing record
                                    // Set status back to PENDING
                                    // Send the request to kafka again
                                }
                            } else {
                                $provider = Provider::getIdFromAlias($bestProvider);
                                if (!$provider) {
                                    continue;
                                }

                                $availableStake = $userBet->stake - $providerTotalBets->totalStake;
                                $stake = $availableStake > $maxBet ? $maxBet : $availableStake;
                                $actualStake = self::actualStake($stake, $userBet, $provider);

                                //check if event is still active
                                $event = Event::getByMarketId($marketId);
                                if (!$event) {
                                    continue;
                                }

                                // get provider account
                                $blockedProviderAccounts = Redis::hGetAll('userBetId:' . $userBetId);
                                $providerAccountId = ProviderAccount::getAssignedAccount($provider->id, $stake, $userBet->is_vip, $event->id, $userBet->odd_type_id, $userBet->market_flag, $walletToken, $blockedProviderAccounts);


                                //Create provider Bets
                                $providerBet = ProviderBet::firstOrCreate([
                                    'user_bet_id' => $userBet->id,
                                    'provider_id' => $provider->id,
                                    'provider_account_id' => $providerAccountId,
                                    'provider_error_message_id' => null,
                                    'status' => 'PENDING',
                                    'bet_id' => null,
                                    'odds' => $maxOdds,
                                    'stake' => $stake,
                                    'to_win' => !in_array($userBet->odd_type_id, $colMinusOne) ? $stake * $maxOdds : $stake * ($maxOdds - 1),
                                    'profit_loss' => null,
                                    'reason' => null,
                                    'settled_date' => null,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => null
                                ]);

                                ProviderBetLog::create([
                                    'provider_bet_id' => $providerBet->id,
                                    'status' => 'PENDING'
                                ]);
                            }
                            
                        }
                    }
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollback();

                $toLogs = [
                    "class"       => "BetQueueManager",
                    "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                    "module"      => "PROCESS",
                    "status_code" => $e->getCode(),
                ];
                monitorLog('monitor_process', 'error', $toLogs);
            }
        }
    }

    public static function actualStake($payloadStake, $userBet, $provider)
    {
        $user = User::find($userBet->user_id);

        $exchangeRatesSWT      = $swoole->exchangeRatesTable;
        $currenciesSWT         = $swoole->currenciesTable;
        $userProviderConfigSWT = $swoole->userProviderConfigTable;

        $userProviderPercentage = -1;
        $userProviderConfigKey  = implode(':', [
            "userId:" . $userBet->user_id,
            "pId:" . $provider->id,
        ]);

        if ($userProviderConfigSWT->exists($userProviderConfigKey)) {
            if (!$userProviderConfigSWT[$userProviderConfigKey]['active']) {
                continue;
            } else {
                $userProviderPercentage = $userProviderConfigSWT[$userProviderConfigKey]['punter_percentage'];
            }
        }

        $userCurrencyInfo = [
            'id'   => $user->currency_id,
            'code' => $currenciesSWT['currencyId:' . $user->currency_id]['code'],
        ];
        
        $exchangeRatesKey = implode(":", [
            "from:" . $userCurrencyInfo['code'],
            "to:" . $providerCurrencyInfo['code'],
        ]);
        $exchangeRate = [
            'id'            => $exchangeRatesSWT[$exchangeRatesKey]['id'],
            'exchange_rate' => $exchangeRatesSWT[$exchangeRatesKey]['exchange_rate'],
        ];

        $percentage = $userProviderPercentage >= 0 ? $userProviderPercentage : $provider->punter_percentage;
        
        $actualStake = ($payloadStake * $exchangeRate['exchange_rate']) / ($percentage / 100);
        if ($request->betType == "BEST_PRICE") {
            $prevStake = $request->stake - $row['max'];
        }

        if ($payloadStake < $row['min']) {
            $toLogs = [
                "class"       => "OrdersController",
                "message"     => trans('game.bet.errors.not-enough-min-stake'),
                "module"      => "API_ERROR",
                "status_code" => 400,
            ];
            monitorLog('monitor_api', 'error', $toLogs);

            throw new BadRequestException(trans('game.bet.errors.not-enough-min-stake'));
        }

        $orderId = uniqid();

        /** ROUNDING UP TO NEAREST 50 */
        $ceil  = ceil($actualStake);

        if ($row['provider'] == 'HG') {
            $last2 = (int) substr($ceil, -2);

            if (($last2 > 0) && ($last2 <= 50)) {
                $actualStake = substr($ceil, 0, -2) . '50';
            } else if ($last2 == 0) {
                $actualStake = $ceil;
            } else if ($last2 > 50) {
                $actualStake  = (int) substr($ceil, 0, -2) + 1;
                $actualStake .= '00';
            }
        } else {
            $actualStake = $ceil;
        }

        return $actualStake;
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
