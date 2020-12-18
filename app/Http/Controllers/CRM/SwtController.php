<?php

namespace App\Http\Controllers\CRM;

use App\Facades\SwooleHandler;
use App\Http\Controllers\Controller;

class SwtController extends Controller
{
    public function __construct()
    {
//        $this->middleware('auth:crm');
    }

    public function index()
    {
        $data = [
            'ws'                  => [
                'name'  => 'WS',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => SwooleHandler::table('wsTable')->count(),
                'memory' => SwooleHandler::table('wsTable')->getMemorySize()
            ],
            'data2Swt'            => [
                'name'  => 'Data to SWT',
                'max'   => 5,
                'count' => SwooleHandler::table('data2SwtTable')->count(),
                'memory' => SwooleHandler::table('data2SwtTable')->getMemorySize()
            ],
            'maintenance'     => [
                'name'  => 'Maintenance',
                'max'   => 64,
                'count' => SwooleHandler::table('maintenanceTable')->count(),
                'memory' => SwooleHandler::table('maintenanceTable')->getMemorySize()
            ],
            'priorityTrigger'     => [
                'name'  => 'Priority Trigger',
                'max'   => 5,
                'count' => SwooleHandler::table('priorityTriggerTable')->count(),
                'memory' => SwooleHandler::table('priorityTriggerTable')->getMemorySize()
            ],
            'updateLeagues'     => [
                'name'  => 'Update Leagues',
                'max'   => 1,
                'count' => SwooleHandler::table('updateLeaguesTable')->count(),
                'memory' => SwooleHandler::table('updateLeaguesTable')->getMemorySize()
            ],
            'userWatchlist'       => [
                'name'  => 'User Watchlist',
                'max'   => 10000,
                'count' => SwooleHandler::table('userWatchlistTable')->count(),
                'memory' => SwooleHandler::table('userWatchlistTable')->getMemorySize()
            ],
            'updatedEvents'       => [
                'name'  => 'Updated Events',
                'max'   => 2000,
                'count' => SwooleHandler::table('updatedEventsTable')->count(),
                'memory' => SwooleHandler::table('updatedEventsTable')->getMemorySize()
            ],
            'getActionLeagues'       => [
                'name'  => 'Get Action Leagues',
                'max'   => 10000,
                'count' => SwooleHandler::table('getActionLeaguesTable')->count(),
                'memory' => SwooleHandler::table('getActionLeaguesTable')->getMemorySize()
            ],
            'consumeLeagues'      => [
                'name'  => 'Consume Leagues',
                'max'   => 10000,
                'count' => SwooleHandler::table('consumeLeaguesTable')->count(),
                'memory' => SwooleHandler::table('consumeLeaguesTable')->getMemorySize()
            ],
            'minmaxMarket'        => [
                'name'  => 'Min Max Market',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => SwooleHandler::table('minmaxMarketTable')->count(),
                'memory' => SwooleHandler::table('minmaxMarketTable')->getMemorySize()
            ],
            'minmaxPayload'       => [
                'name'  => 'Min Max Payload',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => SwooleHandler::table('minmaxPayloadTable')->count(),
                'memory' => SwooleHandler::table('minmaxPayloadTable')->getMemorySize()
            ],
            'eventScraping'       => [
                'name'  => 'Event Scraping',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => SwooleHandler::table('eventScrapingTable')->count(),
                'memory' => SwooleHandler::table('eventScrapingTable')->getMemorySize()
            ],
            'userSelectedLeagues'   => [
                'name'  => 'User Selected Leagues',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => SwooleHandler::table('userSelectedLeaguesTable')->count(),
                'memory' => SwooleHandler::table('userSelectedLeaguesTable')->getMemorySize()
            ],
            'oddTypes'              => [
                'name'  => 'Odd Types',
                'max'   => 100,
                'count' => SwooleHandler::table('oddTypesTable')->count(),
                'memory' => SwooleHandler::table('oddTypesTable')->getMemorySize()
            ],
            'providers'             => [
                'name'  => 'Providers',
                'max'   => 10,
                'count' => SwooleHandler::table('providersTable')->count(),
                'memory' => SwooleHandler::table('providersTable')->getMemorySize()
            ],
            'sports'                => [
                'name'  => 'Sports',
                'max'   => 10,
                'count' => SwooleHandler::table('sportsTable')->count(),
                'memory' => SwooleHandler::table('sportsTable')->getMemorySize()
            ],
            'sportOddTypes'         => [
                'name'  => 'Sport Odd Types',
                'max'   => 100,
                'count' => SwooleHandler::table('sportOddTypesTable')->count(),
                'memory' => SwooleHandler::table('sportOddTypesTable')->getMemorySize()
            ],
            'leagues'               => [
                'name'  => 'Leagues',
                'max'   => 10000,
                'count' => SwooleHandler::table('leaguesTable')->count(),
                'memory' => SwooleHandler::table('leaguesTable')->getMemorySize()
            ],
            'teams'                 => [
                'name'  => 'Teams',
                'max'   => 20000,
                'count' => SwooleHandler::table('teamsTable')->count(),
                'memory' => SwooleHandler::table('teamsTable')->getMemorySize()
            ],
            'events'                 => [
                'name'  => 'Events',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => SwooleHandler::table('eventsTable')->count(),
                'memory' => SwooleHandler::table('eventsTable')->getMemorySize()
            ],
            'eventRecords'                 => [
                'name'  => 'Event Records',
                'max'   => 10000,
                'count' => SwooleHandler::table('eventRecordsTable')->count(),
                'memory' => SwooleHandler::table('eventRecordsTable')->getMemorySize()
            ],
            'oddRecords'                 => [
                'name'  => 'Odd Records',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => SwooleHandler::table('oddRecordsTable')->count(),
                'memory' => SwooleHandler::table('oddRecordsTable')->getMemorySize()
            ],
            'mlEvents'                 => [
                'name'  => 'ML Events',
                'max'   => 10000,
                'count' => SwooleHandler::table('mlEventsTable')->count(),
                'memory' => SwooleHandler::table('mlEventsTable')->getMemorySize()
            ],
            'eventMarkets'                 => [
                'name'  => 'Event Markets',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => SwooleHandler::table('eventMarketsTable')->count(),
                'memory' => SwooleHandler::table('eventMarketsTable')->getMemorySize()
            ],
            'transformed'           => [
                'name'  => 'Transformed',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => SwooleHandler::table('transformedTable')->count(),
                'memory' => SwooleHandler::table('transformedTable')->getMemorySize()
            ],
            'userProviderConfig'    => [
                'name'  => 'User Provider Configuration',
                'max'   => 10000,
                'count' => SwooleHandler::table('userProviderConfigTable')->count(),
                'memory' => SwooleHandler::table('userProviderConfigTable')->getMemorySize()
            ],
            'activeEvents'          => [
                'name'  => 'Active Events',
                'max'   => 1000,
                'count' => SwooleHandler::table('activeEventsTable')->count(),
                'memory' => SwooleHandler::table('activeEventsTable')->getMemorySize()
            ],
            'inactiveEvents'          => [
                'name'  => 'Inactive Events',
                'max'   => 1000,
                'count' => SwooleHandler::table('inactiveEventsTable')->count(),
                'memory' => SwooleHandler::table('inactiveEventsTable')->getMemorySize()
            ],
            'topic'                 => [
                'name'  => 'Topic',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => SwooleHandler::table('topicTable')->count(),
                'memory' => SwooleHandler::table('topicTable')->getMemorySize()
            ],
            'orders'                => [
                'name'  => 'Orders',
                'max'   => 10000,
                'count' => SwooleHandler::table('ordersTable')->count(),
                'memory' => SwooleHandler::table('ordersTable')->getMemorySize()
            ],
            'minMaxRequests'        => [
                'name'  => 'Min Max Requests',
                'max'   => 10000,
                'count' => SwooleHandler::table('minMaxRequestsTable')->count(),
                'memory' => SwooleHandler::table('minMaxRequestsTable')->getMemorySize()
            ],
            'exchangeRates'         => [
                'name'  => 'Exchange Rates',
                'max'   => 100,
                'count' => SwooleHandler::table('exchangeRatesTable')->count(),
                'memory' => SwooleHandler::table('exchangeRatesTable')->getMemorySize()
            ],
            'currencies'            => [
                'name'  => 'Currencies',
                'max'   => 100,
                'count' => SwooleHandler::table('currenciesTable')->count(),
                'memory' => SwooleHandler::table('currenciesTable')->getMemorySize()
            ],
            'users'                 => [
                'name'  => 'Users',
                'max'   => 10000,
                'count' => SwooleHandler::table('usersTable')->count(),
                'memory' => SwooleHandler::table('usersTable')->getMemorySize()
            ],
            'orderPayloads'         => [
                'name'  => 'Order Payloads',
                'max'   => 100000,
                'count' => SwooleHandler::table('orderPayloadsTable')->count(),
                'memory' => SwooleHandler::table('orderPayloadsTable')->getMemorySize()
            ],
            'minmaxOnqueueRequests' => [
                'name'  => 'Min Max Onqueue Requests',
                'max'   => 10000,
                'count' => SwooleHandler::table('minmaxOnqueueRequestsTable')->count(),
                'memory' => SwooleHandler::table('minmaxOnqueueRequestsTable')->getMemorySize()
            ],
            'mlBetId'               => [
                'name'  => 'ML BET ID',
                'max'   => 10000,
                'count' => SwooleHandler::table('mlBetIdTable')->count(),
                'memory' => SwooleHandler::table('mlBetIdTable')->getMemorySize()
            ],
            'scraperRequests'       => [
                'name'  => 'Scraper requests',
                'max'   => env('SWT_MAX_SIZE', 102400),
                'count' => SwooleHandler::table('scraperRequestsTable')->count(),
                'memory' => SwooleHandler::table('scraperRequestsTable')->getMemorySize()
            ],
            'pendingOrdersWithinExpiry'       => [
                'name'  => 'Pending Orders Within Expiry',
                'max'   => 500,
                'count' => SwooleHandler::table('pendingOrdersWithinExpiryTable')->count(),
                'memory' => SwooleHandler::table('pendingOrdersWithinExpiryTable')->getMemorySize()
            ],
            'oddsKafkaPayloads'       => [
                'name'  => 'Odds Kafka Payloads',
                'max'   => 500,
                'count' => SwooleHandler::table('oddsKafkaPayloadsTable')->count(),
                'memory' => SwooleHandler::table('oddsKafkaPayloadsTable')->getMemorySize()
            ],
            'eventsInfo'       => [
                'name'  => 'Odds Info',
                'max'   => 2000,
                'count' => SwooleHandler::table('eventsInfoTable')->count(),
                'memory' => SwooleHandler::table('eventsInfoTable')->getMemorySize()
            ],
            'eventsScored'       => [
                'name'  => 'Events Scored',
                'max'   => 100,
                'count' => SwooleHandler::table('eventsScoredTable')->count(),
                'memory' => SwooleHandler::table('eventsScoredTable')->getMemorySize()
            ],
            'eventNoMarketIds'       => [
                'name'  => 'Event No Market IDs',
                'max'   => 1000,
                'count' => SwooleHandler::table('eventNoMarketIdsTable')->count(),
                'memory' => SwooleHandler::table('eventNoMarketIdsTable')->getMemorySize()
            ],
            'eventHasMarkets'       => [
                'name'  => 'Event Has Markets',
                'max'   => 100,
                'count' => SwooleHandler::table('eventHasMarketsTable')->count(),
                'memory' => SwooleHandler::table('eventHasMarketsTable')->getMemorySize()
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
