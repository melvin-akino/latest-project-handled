<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{
    DB,
    Log
};
use Exception;
use Carbon\Carbon;
class DeleteDuplicateEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:duplicate-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete duplicates in events and master_events tables without deleting records in child/foreign tables.';

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
        try {
            DB::beginTransaction();

            $eventMasterEventIds = DB::table('events')
            ->whereNotNull('master_event_id')
            ->select('master_event_id');

            $memMasterEventIds = DB::table('master_event_markets')
                ->whereNotNull('master_event_id')
                ->select('master_event_id');

            $uwMasterEventIds = DB::table('user_watchlist')
                ->whereNotNull('master_event_id')
                ->select('master_event_id');

            $masterEventIds = $eventMasterEventIds->union($memMasterEventIds)->union($uwMasterEventIds)->pluck('master_event_id');

            $masterEventsNotInOtherTables = DB::table('master_events')
                ->whereNotIn('id', $masterEventIds)
                ->select('master_event_unique_id', 'sport_id','master_league_id', 'master_team_home_id', 'master_team_away_id')
                ->groupBy('master_event_unique_id', 'sport_id','master_league_id', 'master_team_home_id', 'master_team_away_id');

            $masterEventsNotInOtherTables->delete();

            $eventMarketsEventIds = DB::table('event_markets')
                ->whereNotNull('event_id')
                ->select('event_id')
                ->distinct()
                ->pluck('event_id');

            $eventsNotInOtherTables = DB::table('events')
                ->whereNotIn('id', $eventMarketsEventIds)
                ->select('event_identifier', 'sport_id', 'provider_id', 'league_id', 'team_home_id', 'team_away_id')
                ->groupBy('event_identifier', 'sport_id', 'provider_id', 'league_id', 'team_home_id', 'team_away_id');

            $eventsWithDuplicates = DB::table('events')
                ->select('event_identifier', 'sport_id', 'provider_id', 'league_id', 'team_home_id', 'team_away_id')
                ->groupBy('event_identifier', 'sport_id', 'provider_id', 'league_id', 'team_home_id', 'team_away_id')
                ->havingRaw('COUNT(*) > ?', [1])
                ->get();

            foreach($eventsWithDuplicates as $event) {
                $oldestEvent = DB::table('events')->where('event_identifier', $event->event_identifier)->orderBy('id', 'asc')->limit(1);
                $oldestEvent->update([
                    'deleted_at' => Carbon::now()
                ]);
            }

            $eventsNotInOtherTables->delete();

            DB::commit();
            $this->info('Deleted duplicate records in events and master_events table!');
        } catch(Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
            $this->error('Something went wrong in deleting the records.');
        }
    }
}
