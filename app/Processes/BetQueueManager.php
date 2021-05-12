<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\{DB, Log};
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
                        $providerTotalBets = ProviderBet::getTotalStake($userBet);
                        if ($providerTotalBets->totalStake == $userBet->stake) {
                            $providerBetQueues = ProviderBet::getQueue($userBet);
                            if ($providerBetQueues->count() > 0) {
                                // Single loop
                                foreach ($providerBetQueues as $providerBetQueue) {
                                    // get another provider account
                                }
                            }
                        } else {
                            $providerBets = ProviderBet::getQueue($userBet);
                            if ($providerBets->count() > 0) {
                                // Single loop
                                foreach ($providerBets as $providerBet) {
                                    // get another provider account
                                }
                            } else {
                                /**
                                 * @TODO get minmax for all providers by line
                                 */

                                $providerBetPendings = ProviderBet::getPending($userBet);
                                if ($providerBetPendings->count() > 0) {
                                    continue;
                                }


                                /**
                                 * Get the best Odds amongst the user selected providers' minmax
                                 */
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

                                        $currentTime = Carbon::now()->toDateTimeString();
                                        $expireTime  = Carbon::parse($userBet->created_at)->addSeconds($userBet->order_expiry)->toDateTimeString();
                                        if ($currentTime > $expireTime) {
                                            $minMaxData[$minMaxKey]->decr('counter');
                                            continue 2;
                                        }
                                        
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

                                $availableStake = $userBet->stake - $providerTotalBets->totalStake;
                                $stake = $availableStake > $maxBet ? $maxBet : $availableStake;

                                $provider = Provider::getIdFromAlias($bestProvider);
                                if (!$provider) {
                                    continue;
                                }

                                //check if event is still active
                                $event = Event::getByMarketId($marketId);
                                if (!$event) {
                                    continue;
                                }

                                

                                // get provider account
                                $providerAccountId = ProviderAccount::getAssignedAccount($provider->id, $stake, $userBet->is_vip, $event->id, $userBet->odd_type_id, $userBet->market_flag, $walletToken);


                                //Create provider Bets
                                ProviderBet::firstOrCreate([
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

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
