<?php
/**
 * @see https://github.com/hhxsv5/laravel-s/blob/master/Settings-CN.md  Chinese
 * @see https://github.com/hhxsv5/laravel-s/blob/master/Settings.md  English
 */
return [
    'listen_ip'                => env('LARAVELS_LISTEN_IP', '127.0.0.1'),
    'listen_port'              => env('LARAVELS_LISTEN_PORT', 5200),
    'socket_type'              => defined('SWOOLE_SOCK_TCP') ? SWOOLE_SOCK_TCP : 1,
    'enable_coroutine_runtime' => false,
    'server'                   => env('LARAVELS_SERVER', 'LaravelS'),
    'handle_static'            => env('LARAVELS_HANDLE_STATIC', false),
    'laravel_base_path'        => env('LARAVEL_BASE_PATH', base_path()),
    'inotify_reload'           => [
        'enable'        => env('LARAVELS_INOTIFY_RELOAD', false),
        'watch_path'    => base_path(),
        'file_types'    => ['.php'],
        'excluded_dirs' => [],
        'log'           => true,
    ],
    'event_handlers'           => [],
    'websocket'                => [
        'enable' => env('LARAVELS_WEBSOCKET', true),
        'handler' => \App\Services\WebSocketService::class,
    ],
    'sockets'                  => [],
    'processes'                => [
        'data_to_swt' => [
            'class'    => \App\Processes\DataToSwt::class,
            'redirect' => false,
            'pipe' => 0,
            'enable' => true
        ],
        'swt_to_ws' => [
            'class'    => \App\Processes\SwtToWs::class,
            'redirect' => false,
            'pipe'     => 0,
            'enable'   => true
        ],
        'kafka_consume' => [
            'class'    => \App\Processes\KafkaConsume::class,
            'redirect' => false,
            'pipe' => 0,
            'enable' => env('LARAVELS_KAFKA_CONSUME', true)
        ],
        'kafka_produce' => [
            'class'    => \App\Processes\KafkaProduce::class,
            'redirect' => false,
            'pipe' => 0,
            'enable' => env('LARAVELS_KAFKA_PRODUCE', true)
        ],
    ],
    'timer'                    => [
        'enable'        => env('LARAVELS_TIMER', false),
        'jobs'          => [
            // Enable LaravelScheduleJob to run `php artisan schedule:run` every 1 minute, replace Linux Crontab
            //\Hhxsv5\LaravelS\Illuminate\LaravelScheduleJob::class,
            // Two ways to configure parameters:
            // [\App\Jobs\XxxCronJob::class, [1000, true]], // Pass in parameters when registering
            // \App\Jobs\XxxCronJob::class, // Override the corresponding method to return the configuration
            App\Jobs\Timer\BalanceRequestScraperCron::class
        ],
        'max_wait_time' => 5,
    ],
    'events'                   => [],
    'swoole_tables'            => [
        'ws' => [// The Key is table name, will add suffix "Table" to avoid naming conflicts. Here defined a table named "wsTable"
            // key format [uid:$userId] = [value = $fd]
            // key format [fd:$fd] = [value = $userId]
            // key format [userAdditionalLeagues:$userId:sportId:$sportId] = [value = $timestamp]
            // key format [userWatchlist:$userId:masterEventUniqueId:$masterEventUniqueId] = [value = true]
            // key format [userSportLeagueEvents:$userId:league:$multileaguename] = [value = json_encode(data)]
            // key format [leagueLookUpId:unique()] = [value = $leagueName]
            // key format [teamLookUpId:unique()] = [value = $teamName]
            // key format [eventLookUpId:unique()] = [value = slug($masterEventUniqueId)]
            // key format [updatedEvents:$uid] = [value = json_encode([['market_id' => $marketId, 'odds' => $odds], ...])]
            // key format [updatedEvents:$uid] = [value = true]
            'size'   => env('SWT_MAX_SIZE', 102400),// The max size
            'column' => [// Define the columns
                ['name' => 'value', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 10000 ],
            ],
        ],
        'userSelectedLeagues' => [// key format [userId:1:sId:$sportId:schedule:early:uniqueId:uniquid()] => [league_name = $multileaguename, ...]
            'size'   => env('SWT_MAX_SIZE', 102400),// The max size
            'column' => [// Define the columns
                ['name' => 'league_name', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 100],
                ['name' => 'sport_id', 'type' => \Swoole\Table::TYPE_INT],
                ['name' => 'schedule', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 6],
                ['name' => 'user_id', 'type' => \Swoole\Table::TYPE_INT],
            ],
        ],
        'deletedLeagues'    => [// key format [sportId:1:league:multileaguename] => [value = multileaguename]
            'size'   => 10000,// The max size
            'column' => [// Define the columns
                ['name' => 'value',           'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
            ],
        ],
        'oddTypes'       => [ // key format [oddType:$oddType] => [id = $id, type = $oddType]
            'size'   => 1000,
            'column' => [
                [ 'name' => 'id',   'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'type', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 20 ],
            ],
        ],
        'providers'       => [ // key format [providerAlias:strtolower($providerAlias)] => [id = $id, alias = $alias]
            'size'   => 100,
            'column' => [
                ['name' => 'id',                'type' => \Swoole\Table::TYPE_INT ],
                ['name' => 'alias',             'type' => \Swoole\Table::TYPE_STRING, 'size' => 10 ],
                ['name' => 'punter_percentage', 'type' => \Swoole\Table::TYPE_FLOAT ],
                ['name' => 'priority',          'type' => \Swoole\Table::TYPE_INT ],
                ['name' => 'is_enabled',        'type' => \Swoole\Table::TYPE_INT ],
                ['name' => 'currency_id',       'type' => \Swoole\Table::TYPE_INT ],
            ],
        ],
        'sports'          => [ //key format [sId:$sportId] = [name = $sport]
            'size'   => 100,
            'column' => [
                [ 'name' => 'sport', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 50 ],
                [ 'name' => 'id',    'type' => \Swoole\Table::TYPE_INT ],
            ],
        ],
        'sportOddTypes' => [ // key format [sId:$sportId:oddType:slug($oddType)] = [id = $id, ...]
            'size'   => 1000,
            'column' => [
                [ 'name' => 'id',                'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'sport_id',          'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'sport_odd_type_id', 'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'type',              'type' => \Swoole\Table::TYPE_STRING, 'size' => 20 ],
            ],
        ],
        'leagues'         => [ // key format [sId:$sportId:pId:$providerId:leagueLookUpId:$leagueLookUpId] = [id = $multiLeagueId, ...]
            'size'   => env('SWT_MAX_SIZE', 102400),
            'column' => [
                [ 'name' => 'id',                   'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'provider_id',          'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'sport_id',             'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'master_league_name',   'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
                [ 'name' => 'league_name',          'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ]
            ],
        ],
        'teams'           => [ //key format ['pId:$providerId:teamLookUpId:$teamLookUpId] = [id = $teamId, team_name = $teamName]
            'size'   => env('SWT_MAX_SIZE', 102400),
            'column' => [
                [ 'name' => 'id',               'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'team_name',        'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
                [ 'name' => 'master_team_name', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
                [ 'name' => 'provider_id',      'type' => \Swoole\Table::TYPE_INT ],
            ],
        ],
        'events'     => [ //key format [sId:$sportId:pId:$providerId:eventIdentifier:$eventIdentifier] = [id = $id, ...]
            'size'   => env('SWT_MAX_SIZE', 102400),
            'column' => [
                [ 'name' => 'id',                     'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'event_identifier',       'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'sport_id',               'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'master_event_unique_id', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'master_league_name',     'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
                [ 'name' => 'master_home_team_name',  'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
                [ 'name' => 'master_away_team_name',  'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
                [ 'name' => 'game_schedule',          'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'ref_schedule',           'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'score',                  'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'running_time',           'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'home_penalty',           'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'away_penalty',           'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
            ],
        ],
        'eventMarkets'  => [ //key format [pId:$providerId:meUID:$meUID:betIdentifier:$betIdentifier] = [id = $id, ...]
            'size' => env('SWT_MAX_SIZE', 102400),
            'column' => [
                [ 'name' => 'id',                            'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'odd_type_id',                   'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'master_event_market_unique_id', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
                [ 'name' => 'master_event_unique_id',        'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'provider_id',                   'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'odds',                          'type' => \Swoole\Table::TYPE_FLOAT ],
                [ 'name' => 'odd_label',                     'type' => \Swoole\Table::TYPE_STRING, 'size' => 10 ],
                [ 'name' => 'bet_identifier',                'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'is_main',                       'type' => \Swoole\Table::TYPE_INT,    'size' => 1 ],
                [ 'name' => 'market_flag',                   'type' => \Swoole\Table::TYPE_STRING, 'size' => 5 ],
            ],
        ],
        'transformed' => [ //key format [eventIdentifier:$eventIdentifier] = [ts => $ts, ...]
            'size'   => 10000,
            'column' => [ // key format [uid:$uid]
                [ 'name' => 'ts', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 30],
                [ 'name' => 'hash', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 32],
            ],
        ],
        'userProviderConfig' => [
            'size'   => 10000,
            'column' => [ // KEY FORMAT: [userId:$userId:pId:$providerId]
                [ 'name' => 'user_id',     'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'provider_id', 'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'active',      'type' => \Swoole\Table::TYPE_STRING, 'size' => 5 ],
            ],
        ],
        'activeEvents' => [
            'size'   => 10000,
            'column' => [ // KEY FORMAT: [sId:$sportId:pId:$providerId:schedule:$schedule]
                [ 'name' => 'events',     'type' => \Swoole\Table::TYPE_STRING, 'size' => 10000 ],
            ],
        ],
        'topic' => [
            'size'   => env('SWT_MAX_SIZE', 102400),
            'column' => [ // KEY FORMAT: [userId:$userId:unique:<uniqid()>]
                [ 'name' => 'user_id',    'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'topic_name', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
            ],
        ],
        'orders' => [
            'size'   => 10000,
            'column' => [ // KEY FORMAT: [orderId:$oriderId]
                [ 'name' => 'odds',          'type' => \Swoole\Table::TYPE_FLOAT ],
                [ 'name' => 'stake',         'type' => \Swoole\Table::TYPE_FLOAT ],
                [ 'name' => 'to_win',        'type' => \Swoole\Table::TYPE_FLOAT ],
                [ 'name' => 'actual_stake',  'type' => \Swoole\Table::TYPE_FLOAT ],
                [ 'name' => 'actual_to_win', 'type' => \Swoole\Table::TYPE_FLOAT ],
                [ 'name' => 'market_id',     'type' => \Swoole\Table::TYPE_STRING, 'size' => 50 ],
                [ 'name' => 'event_id',      'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'score',         'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'orderExpiry',   'type' => \Swoole\Table::TYPE_STRING, 'size' => 50 ],
                [ 'name' => 'bet_id',        'type' => \Swoole\Table::TYPE_STRING, 'size' => 50 ],
            ],
        ],
        'minMaxRequests' => [
            'size' => 10000,
            'column' => [ // KEY FORMAT: [memUID:$memUID]
                [ 'name' => 'provider',  'type' => \Swoole\Table::TYPE_STRING, 'size' => 5 ],
                [ 'name' => 'market_id', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 50 ],
                [ 'name' => 'sport',     'type' => \Swoole\Table::TYPE_INT ],
            ],
        ],
        'exchangeRates' => [
            'size' => 1000,
            'column' => [ // KEY FORMAT: [from:$from_currency_code:to:$to_currency_code]
                [ 'name' => 'default_amount', 'type' => \Swoole\Table::TYPE_FLOAT ],
                [ 'name' => 'exchange_rate',  'type' => \Swoole\Table::TYPE_FLOAT ],
            ],
        ],
        'currencies' => [
            'size' => 1000,
            'column' => [ // KEY FORMAT: [currencyId:$id:currencyCode:$code]
                [ 'name' => 'id',   'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'code', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 10 ],
            ],
        ],
        'users' => [
            'size' => 10000,
            'column' => [ // KEY FORMAT: [userId:$userId]
                [ 'name' => 'currency_id', 'type' => \Swoole\Table::TYPE_INT ],
            ],
        ],
        'payloads' => [
            'size' => 10000,
            'column' => [
                [ 'name' => 'payload', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 1000 ],
            ],
        ],
        'providerAccounts' => [ // KEY FORMAT: [providerId:$providerId:unique:<uniqid()>]
            'size' => 1000,
            'column' => [
                [ 'name' => 'id', 'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'provider_id', 'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'provider_alias', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 3 ],
                [ 'name' => 'type', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 20 ],
                [ 'name' => 'username', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 50 ],
                [ 'name' => 'punter_percentage', 'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'credits', 'type' => \Swoole\Table::TYPE_FLOAT ]
            ],
        ],
    ],
    'register_providers'       => [
        \Laravel\Passport\PassportServiceProvider::class
    ],
    'cleaners'                 => [
        // See LaravelS's built-in cleaners: https://github.com/hhxsv5/laravel-s/blob/master/Settings.md#cleaners
        Hhxsv5\LaravelS\Illuminate\Cleaners\SessionCleaner::class,
        Hhxsv5\LaravelS\Illuminate\Cleaners\AuthCleaner::class,
    ],
    'destroy_controllers'      => [
        'enable'        => false,
        'excluded_list' => [
            //\App\Http\Controllers\TestController::class,
        ],
    ],
    'swoole'                   => [
        'daemonize'          => env('LARAVELS_DAEMONIZE', false),
        'dispatch_mode'      => 2,
        'reactor_num'        => env('LARAVELS_REACTOR_NUM', function_exists('swoole_cpu_num') ? swoole_cpu_num() * 2 : 4),
        'worker_num'         => env('LARAVELS_WORKER_NUM', function_exists('swoole_cpu_num') ? swoole_cpu_num() * 2 : 8),
        'task_worker_num'    => env('LARAVELS_TASK_WORKER_NUM', function_exists('swoole_cpu_num') ? swoole_cpu_num() * 2 : 8),
        'task_ipc_mode'      => 1,
        'task_max_request'   => env('LARAVELS_TASK_MAX_REQUEST', 8000),
        'task_tmpdir'        => @is_writable('/dev/shm/') ? '/dev/shm' : '/tmp',
        'max_request'        => env('LARAVELS_MAX_REQUEST', 8000),
        'open_tcp_nodelay'   => true,
        'pid_file'           => storage_path('laravels.pid'),
        'log_file'           => storage_path(sprintf('logs/swoole-%s.log', date('Y-m'))),
        'log_level'          => 4,
        'document_root'      => base_path('public'),
        'buffer_output_size' => 2 * 1024 * 1024,
        'socket_buffer_size' => 128 * 1024 * 1024,
        'package_max_length' => 4 * 1024 * 1024,
        'reload_async'       => true,
        'max_wait_time'      => 60,
        'enable_reuse_port'  => true,
        'enable_coroutine'   => true,
        'http_compression'   => false,

        'heartbeat_idle_time' => 60000,
        'heartbeat_check_interval' => 60000,

        // Slow log
        // 'request_slowlog_timeout' => 2,
        // 'request_slowlog_file'    => storage_path(sprintf('logs/slow-%s.log', date('Y-m'))),
        // 'trace_event_worker'      => true,

        /**
         * More settings of Swoole
         * @see https://wiki.swoole.com/wiki/page/274.html  Chinese
         * @see https://www.swoole.co.uk/docs/modules/swoole-server/configuration  English
         */
    ],
];
