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
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->wsTable->count()
            ],
            'data2Swt'            => [
                'name'  => 'Data to SWT',
                'max'   => 5,
                'count' => $swoole->data2SwtTable->count()
            ],
            'maintenance'     => [
                'name'  => 'Maintenance',
                'max'   => 64,
                'count' => $swoole->maintenanceTable->count()
            ],
            'priorityTrigger'     => [
                'name'  => 'Priority Trigger',
                'max'   => 5,
                'count' => $swoole->priorityTriggerTable->count()
            ],
            'newLeagues'     => [
                'name'  => 'New Leagues',
                'max'   => 100,
                'count' => $swoole->newLeaguesTable->count()
            ],
            'userWatchlist'       => [
                'name'  => 'User Watchlist',
                'max'   => 10000,
                'count' => $swoole->userWatchlistTable->count()
            ],
            'updatedEvents'       => [
                'name'  => 'Updated Events',
                'max'   => 2000,
                'count' => $swoole->updatedEventsTable->count()
            ],
            'getActionLeagues'       => [
                'name'  => 'Get Action Leagues',
                'max'   => 10000,
                'count' => $swoole->getActionLeaguesTable->count()
            ],
            'consumeLeagues'      => [
                'name'  => 'Consume Leagues',
                'max'   => 10000,
                'count' => $swoole->consumeLeaguesTable->count()
            ],
            'minmaxMarket'        => [
                'name'  => 'Min Max Market',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->minmaxMarketTable->count()
            ],
            'minmaxPayload'       => [
                'name'  => 'Min Max Payload',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->minmaxPayloadTable->count()
            ],
            'eventScraping'       => [
                'name'  => 'Event Scraping',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->eventScrapingTable->count()
            ],
            'userSelectedLeagues'   => [
                'name'  => 'User Selected Leagues',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->userSelectedLeaguesTable->count()
            ],
            'oddTypes'              => [
                'name'  => 'Odd Types',
                'max'   => 100,
                'count' => $swoole->oddTypesTable->count()
            ],
            'providers'             => [
                'name'  => 'Providers',
                'max'   => 10,
                'count' => $swoole->providersTable->count()
            ],
            'sports'                => [
                'name'  => 'Sports',
                'max'   => 10,
                'count' => $swoole->sportsTable->count()
            ],
            'sportOddTypes'         => [
                'name'  => 'Sport Odd Types',
                'max'   => 100,
                'count' => $swoole->sportOddTypesTable->count()
            ],
            'leagues'               => [
                'name'  => 'Leagues',
                'max'   => 10000,
                'count' => $swoole->leaguesTable->count()
            ],
            'teams'                 => [
                'name'  => 'Teams',
                'max'   => 20000,
                'count' => $swoole->teamsTable->count()
            ],
            'events'                 => [
                'name'  => 'Events',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->eventsTable->count()
            ],
            'eventRecords'                 => [
                'name'  => 'Event Records',
                'max'   => 10000,
                'count' => $swoole->eventsTable->count()
            ],
            'oddRecords'                 => [
                'name'  => 'Odd Records',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->oddRecordsTable->count()
            ],
            'mlEvents'                 => [
                'name'  => 'ML Events',
                'max'   => 10000,
                'count' => $swoole->mlEventsTable->count()
            ],
            'eventMarkets'                 => [
                'name'  => 'Event Markets',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->eventMarketsTable->count()
            ],
            'transformed'           => [
                'name'  => 'Transformed',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->transformedTable->count()
            ],
            'userProviderConfig'    => [
                'name'  => 'User Provider Configuration',
                'max'   => 10000,
                'count' => $swoole->userProviderConfigTable->count()
            ],
            'activeEvents'          => [
                'name'  => 'Active Events',
                'max'   => 1000,
                'count' => $swoole->activeEventsTable->count()
            ],
            'topic'                 => [
                'name'  => 'Topic',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->topicTable->count()
            ],
            'orders'                => [
                'name'  => 'Orders',
                'max'   => 10000,
                'count' => $swoole->ordersTable->count()
            ],
            'minMaxRequests'        => [
                'name'  => 'Min Max Requests',
                'max'   => 10000,
                'count' => $swoole->minMaxRequestsTable->count()
            ],
            'exchangeRates'         => [
                'name'  => 'Exchange Rates',
                'max'   => 100,
                'count' => $swoole->exchangeRatesTable->count()
            ],
            'currencies'            => [
                'name'  => 'Currencies',
                'max'   => 100,
                'count' => $swoole->currenciesTable->count()
            ],
            'users'                 => [
                'name'  => 'Users',
                'max'   => 10000,
                'count' => $swoole->usersTable->count()
            ],
            'orderPayloads'         => [
                'name'  => 'Order Payloads',
                'max'   => 100000,
                'count' => $swoole->orderPayloadsTable->count()
            ],
            'orderRetries'          => [
                'name'  => 'Order Retries',
                'max'   => 10000,
                'count' => $swoole->orderRetriesTable->count()
            ],
            'minmaxOnqueueRequests' => [
                'name'  => 'Min Max Onqueue Requests',
                'max'   => 10000,
                'count' => $swoole->minmaxOnqueueRequestsTable->count()
            ],
            'providerAccounts'      => [
                'name'  => 'Provider Accounts',
                'max'   => 2000,
                'count' => $swoole->providerAccountsTable->count()
            ],
            'mlBetId'               => [
                'name'  => 'ML BET ID',
                'max'   => 10000,
                'count' => $swoole->mlBetIdTable->count()
            ],
            'scraperRequests'       => [
                'name'  => 'Scraper requests',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->scraperRequestsTable->count()
            ],
            'pendingOrdersWithinExpiry'       => [
                'name'  => 'Pending Orders Within Expiry',
                'max'   => 500,
                'count' => $swoole->pendingOrdersWithinExpiryTable->count()
            ],
            'oddsKafkaPayloads'       => [
                'name'  => 'Odds Kafka Payloads',
                'max'   => 500,
                'count' => $swoole->oddsKafkaPayloadsTable->count()
            ],
        ];

        $table = '';
        foreach ($data as $swt) {
            $table .= "<tr>" .
                "<td>{$swt['name']}</td>" .
                "<td>{$swt['max']}</td>" .
                "<td>{$swt['count']}</td>" .
                "</tr>";
        }

        echo <<<EOF
            <h2>Swoole Table Counts</h2>
            <table border="1">
                <thead>
                <tr>
                    <th>SWT</th>
                    <th>MAX COUNT</th>
                    <th>CURRENT COUNT</th>
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
