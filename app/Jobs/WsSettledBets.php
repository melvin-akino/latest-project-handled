<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\UserWallet;
class WsSettledBets implements ShouldQueue
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $providerId, $providerCurrency)
    {
        $this->data             = $data;
        $this->providerId       = $providerId;
        $this->providerCurrency = $providerCurrency;
    }

    private function updateledger($charge, $receiver, $stake,$charge,$c)
    {
        // insert to order_transaction // ssource is now returned stake will ask ria about it 

        // create insertion of wallet and walelt ledger
        // value should all be part of parameter
        $charge_type = 'Credit' // or Debit, paramater
        $receiver = // id of the user 
        $transfer_amount = //stake
        $currency  = // currency id
        $source =// source id 
        /* important :: userwallet::maketransaction will update wallet table wallet depende on $charge_type
          if $charge_tyoe =='Credit' , add amount to wallet  otherwise deduct

        */
        $ledger = UserWallet::makeTransaction($receiver, $transfer_amount, $currency, $source,$charge_type);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status     = strtoupper($this->data->status);
        $balance    = 0;
        $stake      = 0;
        $sourceName = "RETURN_BET";

        if ($status == "WON") {
            $status = "WIN";
        }

        if ($status == "LOSS") {
            $status = "LOSE";
        }

        $orders = DB::table('orders')
            ->where('bet_id', $this->data->bet_id)
            ->first();

        $userWallet = DB::table('wallet')
            ->where('user_id', $orders->user_id)
            ->first();

        $exchangeRate = DB::table('exchange_rates')
            ->where('from_currency_id', $this->providerCurrency)
            ->where('to_currency_id', 1)
            ->first();
        $stakereturntoledger=false

        switch ($status) {
            case 'WIN':
                $stake      = $orders->stake;
                $balance    = $orders->to_win;
                $debit      = 0;
                $credit     = $balance;
                $sourceName = "BET_WINNING";
                $stakereturntoledger = true;
                $charge      = 'Credit';


                break;
            case 'LOSE':
                $balance    = $orders->stake * -1;
                $debit      = $balance;
                $credit     = 0;
                $sourceName = "BET_LOSS";
                $charge       ='Debit';

                break;
            case 'HALF WIN':
                $stake      = $orders->stake;
                $balance    = $orders->to_win / 2;
                $debit      = 0;
                $credit     = $balance;
                $sourceName = "BET_WINNING";
                $stakereturntoledger = true;
                $charge       ='Credit';

                break;
            case 'HALF LOSE':
                $balance    = $orders->stake / 2;
                $debit      = $balance;
                $credit     = 0;
                $sourceName = "BET_LOSS";
                $charge       ='Debit';

                break;
            case 'PUSH':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;
                $charge       ='Credit';

                break;
            case 'DRAW':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;
                 $charge       ='Credit';

                break;
            case 'CANCELLED':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;
                 $charge       ='Credit';

                break;
            case 'REJECTED':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;
                 $charge       ='Credit';

                break;
            case 'ABNORMAL BET':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;
                 $charge       ='Credit';

                break;
            case 'REFUNDED':
                $balance = $orders->stake;
                $debit   = 0;
                $credit  = $balance;
                 $charge       ='Credit';

                break;
        }

        $balance += $stake;

        $sourceId = DB::table('sources')
            ->where('source_name', 'LIKE', $sourceName)
            ->first();

        $returnBetSourceId = DB::table('sources')
            ->where('source_name', 'LIKE', 'RETURN_BET')
            ->first();

        try {

            DB::table('orders')->where('bet_id', $this->data->bet_id)
                ->update(
                    [
                        'status'       => strtoupper($this->data->status),
                        'profit_loss'  => $balance,
                        'reason'       => $this->data->reason,
                        'settled_date' => Carbon::now(),
                        'updated_at'   => Carbon::now(),
                    ]
                );

            $orderLogsId = DB::table('order_logs')
                ->insertGetId(
                    [
                        'provider_id'   => $this->providerId,
                        'sport_id'      => $this->data->sport,
                        'bet_id'        => $this->data->bet_id,
                        'bet_selection' => $orders->bet_selection,
                        'status'        => $status,
                        'user_id'       => $orders->user_id,
                        'reason'        => $this->data->reason,
                        'profit_loss'   => $balance,
                        'order_id'      => $orders->id,
                        'settled_date'  => Carbon::now(),
                        'created_at'    => Carbon::now(),
                        'updated_at'    => Carbon::now(),
                    ]
                );

               $order_transaction_id = DB::table('order_transactions')
                ->insert(
                    [
                        'order_logs_id'       => $orderLogsId,
                        'user_id'             => $orders->user_id,
                        'source_id'           => $sourceId->id,
                        'currency_id'         => $this->providerCurrency,
                        //'wallet_ledger_id'    => $walletLedgerId,
                        'provider_account_id' => $orders->provider_account_id,
                        'reason'              => $this->data->reason,
                        'amount'              => $balance,
                        'created_at'          => Carbon::now(),
                        'updated_at'          => Carbon::now(),
                    ]
                );
                
                // create insertion of wallet and walelt ledger
            // value should all be part of parameter
            $charge_type =  $charge// or Debit, paramater
            $receiver = // id of the user 
            $transfer_amount = 
            $currency  = // currency id
            $source =// source id 
            /* important :: userwallet::maketransaction will update wallet table wallet depende on $charge_type
              if $charge_tyoe =='Credit' , add amount to wallet  otherwise deduct

            */
            $ledger = UserWallet::makeTransaction($receiver, $transfer_amount, $currency, $source,$charge_type);
             DB::table('order_transactions')->where('id', $order_transaction_id)
              ->update(
                    [
                        'wallet_ledger_id'       => $ledger_id,
                    ]
                );


            if ( $stakereturntoledger==true)
            {
                // let us create new order_transaction insertion here with diferent source id 

                $order_transaction_id = DB::table('order_transactions')
                ->insert(
                    [
                        'order_logs_id'       => $orderLogsId,
                        'user_id'             => $orders->user_id,
                        'source_id'           => $sourceId->id,  // apply different source id 
                        'currency_id'         => $this->providerCurrency,
                        //'wallet_ledger_id'    => $walletLedgerId,
                        'provider_account_id' => $orders->provider_account_id,
                        'reason'              => $this->data->reason,
                        'amount'              => $balance,
                        'created_at'          => Carbon::now(),
                        'updated_at'          => Carbon::now(),
                    ]
                );
            $charge_type='Credit';
            $ledger = UserWallet::makeTransaction($receiver, $transfer_amount, $currency, $source,$charge_type);
             DB::table('order_transactions')->where('id', $order_transaction_id)
              ->update(
                    [
                        'wallet_ledger_id'       => $ledger_id,
                    ]
                );

            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
           // \log::info(json_encode($e->getMessage()))
            // create a log or error something 
        }


            /*

        $balance   *= $exchangeRate->exchange_rate;
        $newBalance = $userWallet->balance + $balance;

        DB::table('wallet')->where('user_id', $orders->user_id)
            ->update(
                [
                    'balance'    => $newBalance,
                    'updated_at' => Carbon::now(),
                ]
            );

        $walletLedgerId = DB::table('wallet_ledger')
            ->insertGetId(
                [
                    'wallet_id'  => $userWallet->id,
                    'source_id'  => $sourceId->id,
                    'debit'      => $debit,
                    'credit'     => $credit,
                    'balance'    => $newBalance,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
           
            


        DB::table('order_transactions')
            ->insert(
                [
                    'order_logs_id'       => $orderLogsId,
                    'user_id'             => $orders->user_id,
                    'source_id'           => $sourceId->id,
                    'currency_id'         => $this->providerCurrency,
                    'wallet_ledger_id'    => $walletLedgerId,
                    'provider_account_id' => $orders->provider_account_id,
                    'reason'              => $this->data->reason,
                    'amount'              => $balance,
                    'created_at'          => Carbon::now(),
                    'updated_at'          => Carbon::now(),
                ]
            );

         );*/
         /*

        if ($stake != 0) {
            $returnLedgerId = DB::table('wallet_ledger')
                ->insertGetId(
                    [
                        'wallet_id'  => $userWallet->id,
                        'source_id'  => $returnBetSourceId->id,
                        'debit'      => 0,
                        'credit'     => $stake,
                        'balance'    => $newBalance,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]
                );

            DB::table('order_transactions')
                ->insert(
                    [
                        'order_logs_id'       => $orderLogsId,
                        'user_id'             => $orders->user_id,
                        'source_id'           => $returnBetSourceId->id,
                        'currency_id'         => $this->providerCurrency,
                        'wallet_ledger_id'    => $returnLedgerId,
                        'provider_account_id' => $orders->provider_account_id,
                        'reason'              => 'Returned Stake',
                        'amount'              => $stake,
                        'created_at'          => Carbon::now(),
                        'updated_at'          => Carbon::now(),
                    ]
                );
        }
        */
    }
}
