<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\{DB, Log, Redis};
use Swoole\Http\Server;
use Swoole\{Process, Coroutine};
use App\Facades\SwooleHandler;
use App\Models\{
    Provider,
    ProviderAccount,
    UserBet,
    OddType,
    ProviderBet,
    ProviderBetLog,
    BetWalletTransaction,
    ProviderBetTransaction,
    Blockedline,
    Events
};
use App\User;
use Carbon\Carbon;
use Exception;

class BetQueueManager implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        if ($swoole->data2SwtTable->exist('data2Swt')) {
            $minMaxData     = $swoole->minmaxDataTable;
            $minMaxRequests = $swoole->minMaxRequestsTable;
            $colMinusOne    = OddType::whereIn('type', ['1X2', 'HT 1X2', 'OE'])->pluck('id')->toArray();

            while(true) {
                usleep(5000000);

                try {
                    echo "Initializing queue...\n";
                    Log::channel('bet_queue')->info("Initializing queue...");
                    DB::beginTransaction();

                    $userBets = UserBet::getPending();
                    if ($userBets->count() > 0) {
                        echo "Processing Pending User Bets\n";
                        foreach ($userBets as $userBet) {
                            $marketProviders = explode(',', $userBet->market_providers);
                            // Skip when User Bet Expires and decrement minmax swt counter
                            $currentTime = Carbon::now();
                            $expireTime  = Carbon::createFromFormat("Y-m-d H:i:s", $userBet->created_at);
                            if ($currentTime->diffInSeconds($expireTime) > $userBet->order_expiry) {
                                foreach ($minMaxRequests as $key => $minMaxRequest) {
                                    if (strpos($key, $userBet->mem_uid) !== false) {
                                        foreach ($marketProviders as $marketProvider) {
                                            $provider = Provider::find($marketProvider);
                                            SwooleHandler::decCtr('minMaxRequestsTable', $userBet->mem_uid . ":" . strtolower($provider->alias));
                                        }
                                    }
                                }
                                $userBet = UserBet::find($userBet->id);
                                $userBet->status = 'DONE';
                                $userBet->save();

                                echo "User bet expired: " . $userBet->ml_bet_identifier . "\n";
                                Log::channel('bet_queue')->info([
                                    'msg' => 'Bet already expired',
                                    'data' => $userBet->ml_bet_identifier
                                ]);
                                continue;
                            }
                            
                            // Skip when there's no minmax
                            $minOdds       = null;
                            $maxOdds       = 0;
                            $minBet        = null;
                            $maxBet        = 0;
                            $worstProvider = null;
                            $bestProvider  = null;
                            $marketId      = null;

                            $marketProviders = explode(',', $userBet->market_providers);
                            foreach ($marketProviders as $marketProvider) {
                                $provider = Provider::find($marketProvider);

                                $marketId = null;
                                var_dump($minMaxData->count());
                                foreach ($minMaxData as $minMax) {
                                    if ($minMax['mem_uid'] == $userBet->mem_uid &&
                                        $minMax['provider'] == strtoupper($provider->alias)
                                    ) {
                                        $marketId = $minMax['market_id'];
                                        break;
                                    }
                                }

                                if (empty($marketId)) {
                                    echo "No available market for provider " . $provider->alias . " : " . $userBet->ml_bet_identifier . "\n";
                                    Log::channel('bet_queue')->info([
                                        'msg' => "No available market for provider " . $provider->alias,
                                        'data' => $userBet->ml_bet_identifier
                                    ]);
                                    continue 2; 
                                }


                                $minMaxKey = "minmax-market:" . $marketId;
                                if ($minMaxData->exists($minMaxKey)) {
                                    if (is_null($minOdds)) {
                                        $minOdds       = $minMaxData[$minMaxKey]['odds'];
                                        $minBet        = $minMaxData[$minMaxKey]['max'];
                                        $worstProvider = strtolower($provider->alias);
                                    }

                                    if ($maxOdds <= $minMaxData[$minMaxKey]['odds']) {
                                        $maxOdds = $minMaxData[$minMaxKey]['odds'];
                                        if ($maxBet < $minMaxData[$minMaxKey]['max']) {
                                            $maxBet       = $minMaxData[$minMaxKey]['max'];
                                            $bestProvider = strtolower($provider->alias);
                                            $marketId     = $minMaxData[$minMaxKey]['market_id'];
                                            var_dump($minMaxData->count());
                                            var_dump($minMaxKey);
                                            var_dump($minMaxData[$minMaxKey]);
                                        }
                                    }

                                    if ($minOdds > $minMaxData[$minMaxKey]['odds']) {
                                        $maxOdds       = $minMaxData[$minMaxKey]['odds'];
                                        $minBet        = $minMaxData[$minMaxKey]['max'];
                                        $worstProvider = strtolower($provider->alias);
                                    }
                                } else {
                                    echo "SWT minMaxData Key doesn't exist {$minMaxKey}\n";
                                    Log::channel('bet_queue')->info([
                                        'msg' => "SWT minMaxData Key doesn't exist",
                                        'key' => $minMaxKey
                                    ]);
                                }
                            }

                            if (is_null($worstProvider)) {
                                echo "No updated minmax for {$userBet->ml_bet_identifier}\n";
                                Log::channel('bet_queue')->info([
                                    'msg' => 'No updated minmax',
                                    'data' => (array) $userBet
                                ]);
                                continue;
                            }

                            $providerTotalPlacedBets = ProviderBet::getTotalPlacedStake($userBet);
                            if ($providerTotalPlacedBets == $userBet->stake) {echo "a";
                                foreach ($minMaxRequests as $key => $minMaxRequest) {
                                    if (strpos($key, $userBet->mem_uid) !== false) {
                                        $minMaxRequest->decr($key, 'counter');
                                    }
                                }
                                echo "Fully placed bet for {$userBet->ml_bet_identifier}\n";
                                Log::channel('bet_queue')->info([
                                    'msg' => 'Fully placed bet for',
                                    'data' => (array) $userBet
                                ]);
                                continue;
                            }
                            /**
                             * @TODO check for any queue provider bets
                             * add blocked lines 
                             * assign new account
                             * skip if no new account 
                             * retry queue provider bet and send to kafka
                             */
                            echo "b";
                            $providerBetQueues = ProviderBet::getQueue($userBet);
                            if ($providerBetQueues->count() > 0) {echo "c";
                                foreach ($providerBetQueues as $providerBetQueue) {echo "d";
                                    $providerBet = ProviderBet::find($providerBetQueue->id);
                                    var_dump($marketId);
                                    $event = Events::getByMarketId($marketId);
                                    if (!$event) {echo "e";
                                        continue 2;
                                    }
                                    if (!empty($providerBetQueue->provider_account_id)) {echo "f";
                                        BlockedLine::updateOrCreate([
                                            'event_id'    => $event->id,
                                            'odd_type_id' => $userBet->odd_type_id,
                                            'points'      => $userBet->odds_label,
                                            'line'        => $providerBetQueue->line
                                        ]);
                                    }

                                    $providerBetTransaction = ProviderBetTransaction::where('provider_bet_id', $providerBet->id)
                                                                    ->orderBy('id', 'DESC')
                                                                    ->first();

                                    if ($providerBetTransaction) {echo "grrrr";
                                        $providerBet->status = 'PENDING';
                                        $providerBet->save();

                                        ProviderBetLog::create([
                                            'provider_bet_id' => $providerBet->id,
                                            'status'          => 'PENDING'
                                        ]);

                                        var_dump([
                                            'provider_bet_id'    => $providerBet->id,
                                            'exchange_rate_id'   => $providerBetTransaction->exchange_rate_id,
                                            'actual_stake'       => $providerBetTransaction->actual_stake,
                                            'actual_to_win'      => !in_array($userBet->odd_type_id, $colMinusOne) ? $providerBetTransaction->actual_stake * $providerBet->odds : $providerBetTransaction->actual_stake * ($providerBet->odds - 1),
                                            'actual_profit_loss' => 0.0,
                                            'punter_percentage'  => $providerBetTransaction->punter_percentage,
                                            'exchange_rate'      => $providerBetTransaction->exchange_rate
                                        ]); 
                                    
                                        $providerBetTransactions = ProviderBetTransaction::create([
                                            'provider_bet_id'    => $providerBet->id,
                                            'exchange_rate_id'   => $providerBetTransaction->exchange_rate_id,
                                            'actual_stake'       => $providerBetTransaction->actual_stake,
                                            'actual_to_win'      => !in_array($userBet->odd_type_id, $colMinusOne) ? $providerBetTransaction->actual_stake * $providerBet->odds : $providerBetTransaction->actual_stake * ($providerBet->odds - 1),
                                            'actual_profit_loss' => 0.0,
                                            'punter_percentage'  => $providerBetTransaction->punter_percentage,
                                            'exchange_rate'      => $providerBetTransaction->exchange_rate
                                        ]);
                                    }

                                    /**
                                     * Assign new provider account
                                     */
                                    $provider = Provider::where('alias', strtoupper($bestProvider))->first();
                                    $providerAccount = ProviderAccount::getAssignedAccount($provider->id, $providerBetTransaction->actual_stake, $userBet->is_vip, $event->id, $userBet->odd_type_id, $userBet->market_flag);
                                    if (empty($providerAccount)) {echo "h";
                                        echo "No provider account assigned for provider bet id  {$providerBetQueue->id}\n";
                                        Log::channel('bet_queue')->info([
                                            'msg' => 'No provider account assigned',
                                            'data' => (array) $providerBetQueue
                                        ]);
                                        continue;
                                    }
                                    echo "i";
                                    /**
                                     * Send bet request to kafka
                                     */
                                    $requestId = ((string) Str::uuid()) . "-" . $incrementIds['id'][$i];
                                    $requestTs = getMilliseconds();
                                    $payload   = [
                                        'request_uid' => $requestId,
                                        'request_ts'  => $requestTs,
                                        'sub_command' => 'place',
                                        'command'     => 'bet'
                                    ];

                                    $payload['data'] = [
                                        'provider'         => $bestProvider,
                                        'sport'            => $userBet->sport_id,
                                        'stake'            => $providerBetTransaction->actual_stake,
                                        'odds'             => $providerBet->odds,
                                        'market_id'        => $providerBet->market_id,
                                        'event_id'         => $event->id,
                                        'score'            => $userBet->score_on_bet,
                                        'username'         => $providerAccount->username
                                    ];

                                    echo "j";
                                    KafkaPush::dispatch(
                                        $bestProvider . env('KAFKA_SCRAPE_ORDER_REQUEST_POSTFIX', '_bet_req'),
                                        $payload,
                                        $requestId
                                    );
                                }
                            }
                        }
                    }

                    DB::commit();
                } catch (Exception $e) {
                    DB::rollback();

                    Log::channel('bet_queue')->error([
                        'line' => $e->getLine(),
                        'msg'  => $e->getMessage(),
                        'file' => $e->getFile()
                    ]);

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
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
