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
            'ws'       => [
                'name' => 'WS',
                'count' => $swoole->wsTable->count()
            ],
            'userSelectedLeagues' => [
                'name' => 'User Selected Leagues',
                'count' => $swoole->userSelectedLeaguesTable->count()
            ],
            'deletedLeagues' => [
                'name' => 'Deleted Leagues',
                'count' => $swoole->deletedLeaguesTable->count()
            ],
            'oddTypes' => [
                'name' => 'Odd Types',
                'count' => $swoole->oddTypesTable->count()
            ],
            'providers' => [
                'name' => 'Providers',
                'count' => $swoole->providersTable->count()
            ],
            'sports' => [
                'name' => 'Sports',
                'count' => $swoole->sportsTable->count()
            ],
            'sportOddTypes' => [
                'name' => 'Sport Odd Types',
                'count' => $swoole->sportOddTypesTable->count()
            ],
            'leagues' => [
                'name' => 'Leagues',
                'count' => $swoole->leaguesTable->count()
            ],
            'teams' => [
                'name' => 'teams',
                'count' => $swoole->teamsTable->count()
            ],
            'events' => [
                'name' => 'Events',
                'count' => $swoole->eventsTable->count()
            ],
            'eventMarkets' => [
                'name' => 'Event Markets',
                'count' => $swoole->eventMarketsTable->count()
            ],
            'transformed' => [
                'name' => 'Transformed',
                'count' => $swoole->transformedTable->count()
            ],
            'userProviderConfig' => [
                'name' => 'User Provider Configuration',
                'count' => $swoole->userProviderConfigTable->count()
            ],
            'activeEvents' => [
                'name' => 'Active Events',
                'count' => $swoole->activeEventsTable->count()
            ],
            'topic' => [
                'name' => 'Topic',
                'count' => $swoole->topicTable->count()
            ],
            'orders' => [
                'name' => 'Orders',
                'count' => $swoole->ordersTable->count()
            ],
            'minMaxRequests' => [
                'name' => 'Min Max Requests',
                'count' => $swoole->minMaxRequestsTable->count()
            ],
            'exchangeRates' => [
                'name' => 'Exchange Rates',
                'count' => $swoole->exchangeRatesTable->count()
            ],
            'currencies' => [
                'name' => 'Currencies',
                'count' => $swoole->currenciesTable->count()
            ],
            'users' => [
                'name' => 'Users',
                'count' => $swoole->usersTable->count()
            ],
            'payloads' => [
                'name' => 'Payloads',
                'count' => $swoole->payloadsTable->count()
            ],
            'providerAccounts' => [
                'name' => 'Provider Accounts',
                'count' => $swoole->providerAccountsTable->count()
            ],
            'minMaxQueues' => [
                'name' => 'Min Max Queues',
                'count' => $swoole->minMaxQueuesTable->count()
            ]
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
