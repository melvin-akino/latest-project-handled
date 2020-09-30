<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateOddEvenWinFormulaResult extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $winOE = DB::table('orders')
                   ->where('odd_type_id', 5)
                   ->get();

        foreach ($winOE as $orderData) {
            $orderLogsOE = DB::table('order_logs')
                             ->where('order_id', $orderData->id)
                             ->get();

            foreach ($orderLogsOE as $orderLogData) {
                $providerAccountOrderOE = DB::table('provider_account_orders')
                                            ->where('order_log_id', $orderLogData->id)
                                            ->first();

                if ($providerAccountOrderOE) {
                    if ($orderData->status == 'WIN') {
                        DB::table('provider_account_orders')
                          ->where('id', $providerAccountOrderOE->id)
                          ->update([
                              'actual_to_win'      => $providerAccountOrderOE->actual_stake * ($orderData->odds - 1),
                              'actual_profit_loss' => $providerAccountOrderOE->actual_stake * ($orderData->odds - 1),
                              'updated_at'         => Carbon::now()
                          ]);
                    } else if ($orderData->status == 'HALF WIN') {
                        DB::table('provider_account_orders')
                          ->where('id', $providerAccountOrderOE->id)
                          ->update([
                              'actual_to_win'      => $providerAccountOrderOE->actual_stake * ($orderData->odds - 1),
                              'actual_profit_loss' => ($providerAccountOrderOE->actual_stake * ($orderData->odds - 1)) / 2,
                              'updated_at'         => Carbon::now()
                          ]);
                    } else {
                        DB::table('provider_account_orders')
                          ->where('id', $providerAccountOrderOE->id)
                          ->update([
                              'actual_to_win' => $providerAccountOrderOE->actual_stake * ($orderData->odds - 1),
                              'updated_at'    => Carbon::now()
                          ]);
                    }
                }
            }

            if ($orderData->status == 'WIN') {
                DB::table('orders')
                  ->where('id', $orderData->id)
                  ->update([
                      'to_win'      => $orderData->stake * ($orderData->odds - 1),
                      'profit_loss' => $orderData->stake * ($orderData->odds - 1),
                      'updated_at'  => Carbon::now()
                  ]);
            } else if ($orderData->status == 'HALF WIN') {
                DB::table('orders')
                  ->where('id', $orderData->id)
                  ->update([
                      'to_win'      => $orderData->stake * ($orderData->odds - 1),
                      'profit_loss' => ($orderData->stake * ($orderData->odds - 1)) / 2,
                      'updated_at'  => Carbon::now()
                  ]);
            } else {
                DB::table('orders')
                  ->where('id', $orderData->id)
                  ->update([
                      'to_win'     => $orderData->stake * ($orderData->odds - 1),
                      'updated_at' => Carbon::now()
                  ]);
            }
                
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
