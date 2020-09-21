<?php

use Illuminate\Database\Migrations\Migration;
use App\User;
use App\Models\{Order, OrderLogs, ProviderAccountOrder, UserWallet, OrderTransaction, Source};
use App\Models\CRM\WalletLedger;

class RecreateMissingOrderData extends Migration
{
    protected $emailAddress = 'lc188@npt.com';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            DB::beginTransaction();

            $user         = User::where('email', $this->emailAddress)->first();
            if ($user->exists()) {

                $betSelection = implode("\n", ["Midtjylland v BSC Young Boys", "BSC Young Boys @ 1.06", "HDP -0.25(0 - 0)"]);
                $betId        = 'OU12875312565';
                $stake        = 22657;

                $orderExist = Order::where('bet_id', $betId);
                if ($orderExist->exists()) {
                    // run empty migration
                } else {
                    $orderData = [
                        'user_id'                       => $user->id,
                        'market_id'                     => 'REC4375279',
                        'status'                        => 'LOSE',
                        'bet_id'                        => $betId,
                        'bet_selection'                 => $betSelection,
                        'master_event_market_id'        => '262311',
                        'provider_id'                   => 1,
                        'sport_id'                      => 1,
                        'odds'                          => 1.06,
                        'stake'                         => $stake,
                        'to_win'                        => 24016.42,
                        'provider_account_id'           => 8,
                        'settled_date'                  => '2020-09-16 14:36:50',
                        'created_at'                    => '2020-09-16 10:00:00',
                        'updated_at'                    => '2020-09-16 14:36:50',
                        'reason'                        => '',
                        'profit_loss'                   => -$stake,
                        'order_expiry'                  => "30",
                        'odd_label'                     => -0.25,
                        'ml_bet_identifier'             => 'ML20200916000008',
                        'score_on_bet'                  => '0 - 0',
                        'market_flag'                   => 'AWAY',
                        'odd_type_id'                   => 3,
                        'final_score'                   => '3 - 0',
                        'master_event_market_unique_id' => '355afcac59a4830ad1dc02e3bd281cf9',
                        'master_event_unique_id'        => '20200916-1-332-4375279',
                        'master_league_name'            => 'UEFA Champions League Qualifiers',
                        'master_team_home_name'         => 'Midtjylland',
                        'master_team_away_name'         => 'BSC Young Boys',
                    ];
                    $order     = Order::create($orderData);

                    $orderTransactions = ['PENDING', 'SUCCESS', 'LOSE'];

                    foreach ($orderTransactions as $transaction) {
                        if ($transaction == 'PENDING') {
                            $orderLogData = [
                                'user_id'       => $user->id,
                                'provider_id'   => 1,
                                'sport_id'      => 1,
                                'bet_id'        => '',
                                'bet_selection' => $betSelection,
                                'status'        => 'PENDING',
                                'settled_date'  => null,
                                'created_at'    => '2020-09-16 10:00:00',
                                'updated_at'    => '2020-09-16 10:00:00',
                                'reason'        => '',
                                'profit_loss'   => 0.0,
                                'order_id'      => $order->id
                            ];
                            $orderLog     = OrderLogs::create($orderLogData);

                            $providerAccountOrderData = [
                                'order_log_id'       => $orderLog->id,
                                'exchange_rate_id'   => 1,
                                'actual_stake'       => 50350,
                                'actual_to_win'      => 53371,
                                'actual_profit_loss' => 0,
                                'exchange_rate'      => 1,
                                'created_at'         => '2020-09-16 10:00:00',
                                'updated_at'         => '2020-09-16 10:00:00'
                            ];
                            ProviderAccountOrder::create($providerAccountOrderData);

                            $userWallet     = UserWallet::where('user_id', $user->id);
                            $userWalletData = $userWallet->first();
                            $walletId       = $userWalletData->id;
                            $userBalance    = $userWalletData->balance;
                            $currencyId     = $userWalletData->currency_id;
                            $sourceId       = Source::where('source_name', 'PLACE_BET')->first()->id;
                            $newBalance     = $userBalance - $stake;

                            $userWallet->update(
                                ['balance' => $newBalance]
                            );

                            $ledgerId = WalletLedger::create([
                                'wallet_id' => $walletId,
                                'source_id' => $sourceId,
                                'credit'    => 0,
                                'debit'     => $stake,
                                'balance'   => $newBalance,
                            ])->id;

                            OrderTransaction::create(
                                [
                                    'wallet_ledger_id'    => $ledgerId,
                                    'provider_account_id' => 0,
                                    'order_logs_id'       => $orderLog->id,
                                    'user_id'             => $user->id,
                                    'source_id'           => $sourceId,
                                    'currency_id'         => $currencyId,
                                    'reason'              => "Placed Bet",
                                    'amount'              => $stake,
                                ]
                            );

                        } else if ($transaction == 'SUCCESS') {
                            $orderLogData = [
                                'user_id'       => $user->id,
                                'provider_id'   => 1,
                                'sport_id'      => 1,
                                'bet_id'        => $betId,
                                'bet_selection' => $betSelection,
                                'status'        => 'SUCCESS',
                                'settled_date'  => null,
                                'created_at'    => '2020-09-16 10:00:05',
                                'updated_at'    => '2020-09-16 10:00:05',
                                'reason'        => '',
                                'profit_loss'   => 0,
                                'order_id'      => $order->id
                            ];
                            $orderLog     = OrderLogs::create($orderLogData);

                            $providerAccountOrderData = [
                                'order_log_id'       => $orderLog->id,
                                'exchange_rate_id'   => 1,
                                'actual_stake'       => 50350,
                                'actual_to_win'      => 53371,
                                'actual_profit_loss' => 0,
                                'exchange_rate'      => 1,
                                'created_at'         => '2020-09-16 10:00:05',
                                'updated_at'         => '2020-09-16 10:00:05'
                            ];
                            ProviderAccountOrder::create($providerAccountOrderData);
                        } else if ($transaction == 'LOSE') {
                            $orderLogData = [
                                'user_id'       => $user->id,
                                'provider_id'   => 1,
                                'sport_id'      => 1,
                                'bet_id'        => $betId,
                                'bet_selection' => $betSelection,
                                'status'        => 'LOSE',
                                'settled_date'  => '2020-09-16 14:36:50',
                                'created_at'    => '2020-09-16 14:36:50',
                                'updated_at'    => '2020-09-16 14:36:50',
                                'reason'        => '',
                                'profit_loss'   => -$stake,
                                'order_id'      => $order->id
                            ];
                            $orderLog     = OrderLogs::create($orderLogData);

                            $providerAccountOrderData = [
                                'order_log_id'       => $orderLog->id,
                                'exchange_rate_id'   => 1,
                                'actual_stake'       => 50350,
                                'actual_to_win'      => 53371,
                                'actual_profit_loss' => -50350,
                                'exchange_rate'      => 1,
                                'created_at'         => '2020-09-16 14:36:50',
                                'updated_at'         => '2020-09-16 14:36:50'
                            ];
                            ProviderAccountOrder::create($providerAccountOrderData);

                            $userWallet     = UserWallet::where('user_id', $user->id);
                            $userWalletData = $userWallet->first();
                            $walletId       = $userWalletData->id;
                            $userBalance    = $userWalletData->balance;
                            $currencyId     = $userWalletData->currency_id;
                            $sourceId       = Source::where('source_name', 'BET_LOSE')->first()->id;
                            $newBalance     = $userBalance;

                            $userWallet->update(
                                ['balance' => $newBalance]
                            );

                            $ledgerId = WalletLedger::create([
                                'wallet_id' => $walletId,
                                'source_id' => $sourceId,
                                'credit'    => 0,
                                'debit'     => 0,
                                'balance'   => $newBalance,
                            ])->id;

                            OrderTransaction::create([
                                'order_logs_id'       => $orderLog->id,
                                'user_id'             => $user->id,
                                'source_id'           => $sourceId,
                                'currency_id'         => $currencyId,
                                'wallet_ledger_id'    => $ledgerId,
                                'provider_account_id' => 8,
                                'reason'              => '',
                                'amount'              => 0
                            ]);
                        }
                    }
                }
            } else {
                throw new Exception('User not found');
            }


            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            \Illuminate\Support\Facades\Log::error($e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
