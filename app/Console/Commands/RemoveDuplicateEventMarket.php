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
            DB::table('orders')->groupBy('master_event_market_id')->select('master_event_market_id')->orderBy('master_event_market_id', 'ASC')->chunk(10000, function ($ids) {
                $memOrderIds = $ids->pluck('master_event_market_id')->toArray();

                $memDeleteNotIn = DB::table('master_event_markets')
                    ->whereNotIn('id', $memOrderIds)
                    ->orderBy('id', 'ASC');

                $memDeleteNotIn->chunk(10000, function ($memID) {
                    $memIDs = $memID->pluck('id')->toArray();

                    $emDeleteInMEM = DB::table('event_markets')
                        ->groupBy('master_event_market_id')
                        ->select('master_event_market_id')
                        ->whereIn('master_event_market_id', $memIDs)
                        ->orderBy('master_event_market_id', 'ASC');

                    $emDeleteDuplicateBetID = DB::table('event_markets')
                        ->whereNotIn('master_event_market_id', $memIDs)
                        ->select('bet_identifier', 'master_event_market_id')
                        ->groupBy('bet_identifier', 'master_event_market_id')
                        ->havingRaw('COUNT(*) > 1')
                        ->orderBy('bet_identifier', 'ASC');

                    $emDeleteDuplicateBetID->chunk(10000, function ($eventMarkets) {
                        $softDel = $eventMarkets->pluck('bet_identifier')->toArray();

                        DB::table('event_markets')
                            ->whereIn('bet_identifier', $softDel)
                            ->update([
                                'deleted_at' => Carbon::now()
                            ]);
                    });

                    $emDeleteDuplicateMEMID = DB::table('event_markets')
                        ->whereNotIn('master_event_market_id', $memIDs)
                        ->select('master_event_market_id')
                        ->groupBy('master_event_market_id')
                        ->havingRaw('COUNT(*) > 1')
                        ->orderBy('master_event_market_id', 'ASC');

                    $emDeleteDuplicateMEMID->chunk(10000, function ($eventMarkets) {
                        $softDel = $eventMarkets->pluck('master_event_market_id')->toArray();

                        DB::table('event_markets')
                            ->whereIn('master_event_market_id', $softDel)
                            ->update([
                                'deleted_at' => Carbon::now()
                            ]);
                    });

                    $emDeleteInMEM->chunk(10000, function ($eventMarkets) {
                        $softDel = $eventMarkets->pluck('master_event_market_id')->toArray();

                        DB::table('event_markets')
                            ->whereIn('master_event_market_id', $softDel)
                            ->delete();
                    });
                });

                $emDeleteNull = DB::table('event_markets')
                    ->whereNull('master_event_market_id')
                    ->orderBy('master_event_market_id', 'ASC');

                $emDeleteNull->chunk(10000, function ($eventMarkets) {
                    $softDel = $eventMarkets->pluck('id')->toArray();

                    DB::table('event_markets')
                        ->whereIn('id', $softDel)
                        ->delete();
                });

                $memDeleteNotIn->chunk(10000, function ($eventMarkets) {
                    $softDel = $eventMarkets->pluck('id')->toArray();

                    DB::table('master_event_market_logs')
                        ->whereIn('master_event_market_id', $softDel)
                        ->delete();

                    DB::table('master_event_markets')
                        ->whereIn('id', $softDel)
                        ->delete();
                });
            });

            DB::commit();

            $this->info('Done!');
        } catch (Exception $e) {
            DB::rollback();

            $this->line($e->getMessage());
        }
    }
}
