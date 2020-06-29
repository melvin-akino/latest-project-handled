<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Console\Command;

use Carbon\Carbon;

class RemoveDuplicateEventMarket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'markets:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate Event Market records.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::beginTransaction();

        $this->line('Cleaning Event Market Database Tables...');

        try {
            $memIdOrders = DB::table('orders')
                ->pluck('master_event_market_id')
                ->toArray();

            $memLogsDeleteNotIn = DB::table('master_event_market_logs')
                ->whereNotIn('master_event_market_id', $memIdOrders);

            $memDeleteNotIn = DB::table('master_event_markets')
                ->whereNotIn('id', $memIdOrders);

            $memIDs = $memDeleteNotIn->pluck('id')
                ->toArray();

            $emDeleteInMEM = DB::table('event_markets')
                ->whereIn('master_event_market_id', $memIDs);

            $emDeleteNull = DB::table('event_markets')
                ->whereNull('master_event_market_id');

            $emDeleteDuplicate = DB::table('event_markets')
                ->whereNotIn('master_event_market_id', $memIDs)
                ->select('master_event_market_id')
                ->groupBy('master_event_market_id')
                ->havingRaw('COUNT(*) > 1')
                ->update([ 'deleted_at' => Carbon::now() ]);

            $memLogsDeleteNotIn->delete();
            $emDeleteInMEM->delete();
            $emDeleteNull->delete();
            $memDeleteNotIn->delete();

            DB::commit();

            $this->info('Done!');
        } catch (Exception $e) {
            DB::rollback();

            $this->line($e->getMessage());
        }
    }
}
