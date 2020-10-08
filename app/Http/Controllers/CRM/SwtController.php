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
                'count' => $swoole->wsTable->count(),
                'memory' => $swoole->wsTable->getMemorySize()
            ],
            'data2Swt'            => [
                'name'  => 'Data to SWT',
                'max'   => 5,
                'count' => $swoole->data2SwtTable->count(),
                'memory' => $swoole->data2SwtTable->getMemorySize()
            ],
            'maintenance'     => [
                'name'  => 'Maintenance',
                'max'   => 64,
                'count' => $swoole->maintenanceTable->count(),
                'memory' => $swoole->maintenanceTable->getMemorySize()
            ],
            'priorityTrigger'     => [
                'name'  => 'Priority Trigger',
                'max'   => 5,
                'count' => $swoole->priorityTriggerTable->count(),
                'memory' => $swoole->priorityTriggerTable->getMemorySize()
            ],
            'updateLeagues'     => [
                'name'  => 'Update Leagues',
                'max'   => 100,
                'count' => $swoole->updateLeaguesTable->count(),
                'memory' => $swoole->updateLeaguesTable->getMemorySize()
            ],
            'userWatchlist'       => [
                'name'  => 'User Watchlist',
                'max'   => 10000,
                'count' => $swoole->userWatchlistTable->count(),
                'memory' => $swoole->userWatchlistTable->getMemorySize()
            ],
            'updatedEvents'       => [
                'name'  => 'Updated Events',
                'max'   => 2000,
                'count' => $swoole->updatedEventsTable->count(),
                'memory' => $swoole->updatedEventsTable->getMemorySize()
            ],
            'getActionLeagues'       => [
                'name'  => 'Get Action Leagues',
                'max'   => 10000,
                'count' => $swoole->getActionLeaguesTable->count(),
                'memory' => $swoole->getActionLeaguesTable->getMemorySize()
            ],
            'consumeLeagues'      => [
                'name'  => 'Consume Leagues',
                'max'   => 10000,
                'count' => $swoole->consumeLeaguesTable->count(),
                'memory' => $swoole->consumeLeaguesTable->getMemorySize()
            ],
            'minmaxMarket'        => [
                'name'  => 'Min Max Market',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->minmaxMarketTable->count(),
                'memory' => $swoole->minmaxMarketTable->getMemorySize()
            ],
            'minmaxPayload'       => [
                'name'  => 'Min Max Payload',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->minmaxPayloadTable->count(),
                'memory' => $swoole->minmaxPayloadTable->getMemorySize()
            ],
            'eventScraping'       => [
                'name'  => 'Event Scraping',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->eventScrapingTable->count(),
                'memory' => $swoole->eventScrapingTable->getMemorySize()
            ],
            'userSelectedLeagues'   => [
                'name'  => 'User Selected Leagues',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->userSelectedLeaguesTable->count(),
                'memory' => $swoole->userSelectedLeaguesTable->getMemorySize()
            ],
            'oddTypes'              => [
                'name'  => 'Odd Types',
                'max'   => 100,
                'count' => $swoole->oddTypesTable->count(),
                'memory' => $swoole->oddTypesTable->getMemorySize()
            ],
            'providers'             => [
                'name'  => 'Providers',
                'max'   => 10,
                'count' => $swoole->providersTable->count(),
                'memory' => $swoole->providersTable->getMemorySize()
            ],
            'sports'                => [
                'name'  => 'Sports',
                'max'   => 10,
                'count' => $swoole->sportsTable->count(),
                'memory' => $swoole->sportsTable->getMemorySize()
            ],
            'sportOddTypes'         => [
                'name'  => 'Sport Odd Types',
                'max'   => 100,
                'count' => $swoole->sportOddTypesTable->count(),
                'memory' => $swoole->sportOddTypesTable->getMemorySize()
            ],
            'leagues'               => [
                'name'  => 'Leagues',
                'max'   => 10000,
                'count' => $swoole->leaguesTable->count(),
                'memory' => $swoole->leaguesTable->getMemorySize()
            ],
            'teams'                 => [
                'name'  => 'Teams',
                'max'   => 20000,
                'count' => $swoole->teamsTable->count(),
                'memory' => $swoole->teamsTable->getMemorySize()
            ],
            'events'                 => [
                'name'  => 'Events',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->eventsTable->count(),
                'memory' => $swoole->eventsTable->getMemorySize()
            ],
            'eventRecords'                 => [
                'name'  => 'Event Records',
                'max'   => 10000,
                'count' => $swoole->eventRecordsTable->count(),
                'memory' => $swoole->eventRecordsTable->getMemorySize()
            ],
            'oddRecords'                 => [
                'name'  => 'Odd Records',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->oddRecordsTable->count(),
                'memory' => $swoole->oddRecordsTable->getMemorySize()
            ],
            'mlEvents'                 => [
                'name'  => 'ML Events',
                'max'   => 10000,
                'count' => $swoole->mlEventsTable->count(),
                'memory' => $swoole->mlEventsTable->getMemorySize()
            ],
            'eventMarkets'                 => [
                'name'  => 'Event Markets',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->eventMarketsTable->count(),
                'memory' => $swoole->eventMarketsTable->getMemorySize()
            ],
            'transformed'           => [
                'name'  => 'Transformed',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->transformedTable->count(),
                'memory' => $swoole->transformedTable->getMemorySize()
            ],
            'userProviderConfig'    => [
                'name'  => 'User Provider Configuration',
                'max'   => 10000,
                'count' => $swoole->userProviderConfigTable->count(),
                'memory' => $swoole->userProviderConfigTable->getMemorySize()
            ],
            'activeEvents'          => [
                'name'  => 'Active Events',
                'max'   => 1000,
                'count' => $swoole->activeEventsTable->count(),
                'memory' => $swoole->activeEventsTable->getMemorySize()
            ],
            'inactiveEvents'          => [
                'name'  => 'Inactive Events',
                'max'   => 1000,
                'count' => $swoole->inactiveEventsTable->count()
            ],
            'topic'                 => [
                'name'  => 'Topic',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->topicTable->count(),
                'memory' => $swoole->topicTable->getMemorySize()
            ],
            'orders'                => [
                'name'  => 'Orders',
                'max'   => 10000,
                'count' => $swoole->ordersTable->count(),
                'memory' => $swoole->ordersTable->getMemorySize()
            ],
            'minMaxRequests'        => [
                'name'  => 'Min Max Requests',
                'max'   => 10000,
                'count' => $swoole->minMaxRequestsTable->count(),
                'memory' => $swoole->minMaxRequestsTable->getMemorySize()
            ],
            'exchangeRates'         => [
                'name'  => 'Exchange Rates',
                'max'   => 100,
                'count' => $swoole->exchangeRatesTable->count(),
                'memory' => $swoole->exchangeRatesTable->getMemorySize()
            ],
            'currencies'            => [
                'name'  => 'Currencies',
                'max'   => 100,
                'count' => $swoole->currenciesTable->count(),
                'memory' => $swoole->currenciesTable->getMemorySize()
            ],
            'users'                 => [
                'name'  => 'Users',
                'max'   => 10000,
                'count' => $swoole->usersTable->count(),
                'memory' => $swoole->usersTable->getMemorySize()
            ],
            'orderPayloads'         => [
                'name'  => 'Order Payloads',
                'max'   => 100000,
                'count' => $swoole->orderPayloadsTable->count(),
                'memory' => $swoole->orderPayloadsTable->getMemorySize()
            ],
            'orderRetries'          => [
                'name'  => 'Order Retries',
                'max'   => 10000,
                'count' => $swoole->orderRetriesTable->count(),
                'memory' => $swoole->orderRetriesTable->getMemorySize()
            ],
            'minmaxOnqueueRequests' => [
                'name'  => 'Min Max Onqueue Requests',
                'max'   => 10000,
                'count' => $swoole->minmaxOnqueueRequestsTable->count(),
                'memory' => $swoole->minmaxOnqueueRequestsTable->getMemorySize()
            ],
            'providerAccounts'      => [
                'name'  => 'Provider Accounts',
                'max'   => 2000,
                'count' => $swoole->providerAccountsTable->count(),
                'memory' => $swoole->providerAccountsTable->getMemorySize()
            ],
            'mlBetId'               => [
                'name'  => 'ML BET ID',
                'max'   => 10000,
                'count' => $swoole->mlBetIdTable->count(),
                'memory' => $swoole->mlBetIdTable->getMemorySize()
            ],
            'scraperRequests'       => [
                'name'  => 'Scraper requests',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => $swoole->scraperRequestsTable->count(),
                'memory' => $swoole->scraperRequestsTable->getMemorySize()
            ],
            'pendingOrdersWithinExpiry'       => [
                'name'  => 'Pending Orders Within Expiry',
                'max'   => 500,
                'count' => $swoole->pendingOrdersWithinExpiryTable->count(),
                'memory' => $swoole->pendingOrdersWithinExpiryTable->getMemorySize()
            ],
            'oddsKafkaPayloads'       => [
                'name'  => 'Odds Kafka Payloads',
                'max'   => 500,
                'count' => $swoole->oddsKafkaPayloadsTable->count(),
                'memory' => $swoole->oddsKafkaPayloadsTable->getMemorySize()
            ],
            'eventsInfo'       => [
                'name'  => 'Odds Info',
                'max'   => 2000,
                'count' => $swoole->eventsInfoTable->count(),
                'memory' => $swoole->eventsInfoTable->getMemorySize()
            ],
            'eventsScored'       => [
                'name'  => 'Events Scored',
                'max'   => 100,
                'count' => $swoole->eventsScoredTable->count(),
                'memory' => $swoole->eventsScoredTable->getMemorySize()
            ],
            'eventNoMarketIds'       => [
                'name'  => 'Event No Market IDs',
                'max'   => 1000,
                'count' => $swoole->eventNoMarketIdsTable->count(),
                'memory' => $swoole->eventNoMarketIdsTable->getMemorySize()
            ],
            'eventHasMarkets'       => [
                'name'  => 'Event Has Markets',
                'max'   => 100,
                'count' => $swoole->eventHasMarketsTable->count(),
                'memory' => $swoole->eventHasMarketsTable->getMemorySize()
            ],
        ];

        $table = '';
        foreach ($data as $swt) {
            $table .= "<tr>" .
                "<td>{$swt['name']}</td>" .
                "<td>{$swt['max']}</td>" .
                "<td>{$swt['count']}</td>" .
                "<td>{$swt['memory']}</td>" .
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
                    <th>GET MEMORY SIZE</th>
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
