<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;

class SwtController extends Controller
{
    public function __construct()
    {
//        $this->middleware('auth:crm');
    }

    public function index()
    {
        $swoole = app('swoole');


        $data = [
            'ws'                  => [
                'name'  => 'WS',
                'count' => $swoole->wsTable->count()
            ],
            'data2Swt'            => [
                'name'  => 'Data to SWT',
                'count' => $swoole->data2SwtTable->count()
            ],
            'priorityTrigger'     => [
                'name'  => 'Priority Trigger',
                'count' => $swoole->priorityTriggerTable->count()
            ],
            'userWatchlist'       => [
                'name'  => 'User Watchlist',
                'count' => $swoole->userWatchlistTable->count()
            ],
            'updatedEvents'       => [
                'name'  => 'Updated Events',
                'count' => $swoole->updatedEventsTable->count()
            ],
            'getActionLeagues'    => [
                'name'  => 'Get Action Leagues',
                'count' => $swoole->getActionLeaguesTable->count()
            ],
            'consumeLeagues'      => [
                'name'  => 'Consume Leagues',
                'count' => $swoole->consumeLeaguesTable->count()
            ],
            'minmaxMarket'        => [
                'name'  => 'Min Max Market',
                'count' => $swoole->minmaxMarketTable->count()
            ],
            'minmaxPayload'       => [
                'name'  => 'Min Max Payload',
                'count' => $swoole->minmaxPayloadTable->count()
            ],
            'eventScraping'       => [
                'name'  => 'Event Scraping',
                'count' => $swoole->eventScrapingTable->count()
            ],


            'userSelectedLeagues'   => [
                'name'  => 'User Selected Leagues',
                'count' => $swoole->userSelectedLeaguesTable->count()
            ],
            'oddTypes'              => [
                'name'  => 'Odd Types',
                'count' => $swoole->oddTypesTable->count()
            ],
            'providers'             => [
                'name'  => 'Providers',
                'count' => $swoole->providersTable->count()
            ],
            'sports'                => [
                'name'  => 'Sports',
                'count' => $swoole->sportsTable->count()
            ],
            'sportOddTypes'         => [
                'name'  => 'Sport Odd Types',
                'count' => $swoole->sportOddTypesTable->count()
            ],
            'leagues'               => [
                'name'  => 'Leagues',
                'count' => $swoole->leaguesTable->count()
            ],
            'teams'                 => [
                'name'  => 'Teams',
                'count' => $swoole->teamsTable->count()
            ],
            'masterTeams'           => [
                'name'  => 'Master Teams',
                'count' => $swoole->masterTeams->count()
            ],
            'transformed'           => [
                'name'  => 'Transformed',
                'count' => $swoole->transformedTable->count()
            ],
            'userProviderConfig'    => [
                'name'  => 'User Provider Configuration',
                'count' => $swoole->userProviderConfigTable->count()
            ],
            'activeEvents'          => [
                'name'  => 'Active Events',
                'count' => $swoole->activeEventsTable->count()
            ],
            'topic'                 => [
                'name'  => 'Topic',
                'count' => $swoole->topicTable->count()
            ],
            'orders'                => [
                'name'  => 'Orders',
                'count' => $swoole->ordersTable->count()
            ],
            'minMaxRequests'        => [
                'name'  => 'Min Max Requests',
                'count' => $swoole->minMaxRequestsTable->count()
            ],
            'exchangeRates'         => [
                'name'  => 'Exchange Rates',
                'count' => $swoole->exchangeRatesTable->count()
            ],
            'currencies'            => [
                'name'  => 'Currencies',
                'count' => $swoole->currenciesTable->count()
            ],
            'users'                 => [
                'name'  => 'Users',
                'count' => $swoole->usersTable->count()
            ],
            'orderPayloads'         => [
                'name'  => 'Order Payloads',
                'count' => $swoole->orderPayloadsTable->count()
            ],
            'orderRetries'          => [
                'name'  => 'Order Retries',
                'count' => $swoole->orderRetriesTable->count()
            ],
            'minmaxOnqueueRequests' => [
                'name'  => 'Min Max Onqueue Requests',
                'count' => $swoole->minmaxOnqueueRequestsTable->count()
            ],
            'providerAccounts'      => [
                'name'  => 'Provider Accounts',
                'count' => $swoole->providerAccountsTable->count()
            ],
            'mlBetId'               => [
                'name'  => 'ML BET ID',
                'count' => $swoole->mlBetIdTable->count()
            ],
            'scraperRequests'       => [
                'name'  => 'Scraper requests',
                'count' => $swoole->scraperRequestsTable->count()
            ],
        ];

        $table = '';
        foreach ($data as $swt) {
            $table .= "<tr>" .
                "<td>{$swt['name']}</td>" .
                "<td>{$swt['count']}</td>" .
                "</tr>";
        }

        echo <<<EOF
            <h2>Swoole Table Counts</h2>
            <table border="1">
                <thead>
                <tr>
                    <th>SWT</th>
                    <th>COUNT</th>
                </tr>
                </thead>
                <tbody>
                    {$table}
                </tbody>
            </table>
EOF;
        return null;
    }
}
