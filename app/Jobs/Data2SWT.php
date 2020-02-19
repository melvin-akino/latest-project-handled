<?php

namespace App\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Data2SWT
{
    use Dispatchable;

    public function handle()
    {
        $providers = DB::connection(config('database.crm_default'))->table('providers')->get();

        $providersTable = app('swoole')->providersTable;
        array_map(function ($provider) use ($providersTable) {
            $providersTable->set(strtolower($provider->alias), ['id' => $provider->id, 'alias' => $provider->alias]);
        }, $providers->toArray());

        //@TODO table source will be changed
        $leagues = DB::table('master_leagues')
            ->join('master_league_links', 'master_leagues.id', 'master_league_links.master_league_id')
            ->join('leagues', 'leagues.id', 'master_league_links.master_league_id')
            ->select('master_leagues.id', 'master_leagues.sport_id', 'multi_league', 'leagues.provider_id', 'leagues.league')
            ->get();

        $leaguesTable = app('swoole')->leaguesTable;
        array_map(function ($league) use ($leaguesTable) {
            $leaguesTable->set(implode(':', [$league->sport_id, $league->provider_id, Str::slug($league->league)]),
                [
                    'id'           => $league->id,
                    'sport_id'     => $league->sport_id,
                    'provider_id'  => $league->provider_id,
                    'multi_league' => $league->multi_league
                ]
            );
        }, $leagues->toArray());
    }
}
