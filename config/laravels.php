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
        'data2swt' => [
            'class'    => \App\Processes\Data2SWT::class,
            'redirect' => false,
            'pipe' => 0,
            'enable' => env('LARAVELS_DATA2SWT', true)
        ],
        'kafka_consume' => [
            'class'    => \App\Processes\KafkaConsume::class,
            'redirect' => false,
            'pipe' => 0,
            'enable' => env('LARAVELS_KAFKA_CONSUME', true)
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
        ],
        'max_wait_time' => 5,
    ],
    'events'                   => [],
    'swoole_tables'            => [
        'ws' => [// The Key is table name, will add suffix "Table" to avoid naming conflicts. Here defined a table named "wsTable"
            // key format [uid:$userId] = [value = $fd]
            // key format [fd:$fd] = [value = $userId]
            // key format [userAdditionalLeagues:$userId:sportId:$sportId] = [value = $timestamp]
            // key format [userSelectedLeagues:$userId:sportId:1:league:multileaguename] = [value = $multileaguename]
            // key format [userWatchlist:$userId:league:$multileaguename] = [value = json_encode(data)]
            // key format [userSportLeagueEvents:$userId:league:$multileaguename] = [value = json_encode(data)]
            'size'   => 102400,// The max size
            'column' => [// Define the columns
                ['name' => 'value', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 30],
            ],
        ],
        'deletedLeagues'    => [// key format [sportId:1:league:multileaguename] => [value = multileaguename]
            'size'   => 102400,// The max size
            'column' => [// Define the columns
                ['name' => 'value',           'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
            ],
        ],
        'kafka' => [// The Key is table name, will add suffix "Table" to avoid naming conflicts. Here defined a table named "wsTable"
            'size'   => 102400,// The max size
            'column' => [// Define the columns
                ['name' => 'value', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 5000],
            ],
        ],
        'oddTypes'       => [ // key format [oddType:$oddType] => [id = $id, type = $oddType]
            'size'   => 102400,
            'column' => [
                [ 'name' => 'id',   'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'type', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 20 ],
            ],
        ],
        'providers'       => [ // key format [providerAlias:strtolower($providerAlias)] => [id = $id, alias = $alias]
            'size'   => 500,
            'column' => [
                ['name' => 'id',    'type' => \Swoole\Table::TYPE_INT],
                ['name' => 'alias', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 10],
            ],
        ],
        'sports'          => [ //key format [sId:$sportId] = [name = $sport]
            'size'   => 102400,
            'column' => [
                [ 'name' => 'sport', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 50 ],
                [ 'name' => 'id',    'type' => \Swoole\Table::TYPE_INT ],
            ],
        ],
        'sportOddTypes' => [ // key format [sId:$sportId:oddType:slug($oddType)] = [id = $id, ...]
            'size'   => 102400,
            'column' => [
                [ 'name' => 'id',                'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'sport_id',          'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'sport_odd_type_id', 'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'type',              'type' => \Swoole\Table::TYPE_STRING, 'size' => 20 ],
            ],
        ],
        'leagues'         => [ // key format [sId:$sportId:pId:$providerId:league:$rawLeague] = [id = $multiLeagueId, ...]
            'size'   => 102400,
            'column' => [
                [ 'name' => 'id',           'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'provider_id',  'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'sport_id',     'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'multi_league', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
                [ 'name' => 'league_id',    'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'match_count',  'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'timestamp',    'type' => \Swoole\Table::TYPE_INT ],
            ],
        ],
        'rawLeagues'         => [ // key format [sId:$sportId:pId:$providerId:league:slug($league)] = [id = $multiLeagueId, ...]
            'size'   => 102400,
            'column' => [
                [ 'name' => 'id',          'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'provider_id', 'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'sport_id',    'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'league',      'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
            ],
        ],
        'teams'           => [ //key format [team:slug($team)] = [id = $teamId, team_name = $teamName]
            'size'   => 102400,
            'column' => [
                [ 'name' => 'id',          'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'multi_team',  'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
                [ 'name' => 'provider_id', 'type' => \Swoole\Table::TYPE_INT ],
            ],
        ],
        'rawTeams'           => [ //key format [pId:$providerId:team:slug($team)] = [id = $teamId, team_name = $teamName]
            'size'   => 102400,
            'column' => [
                [ 'name' => 'id',          'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'team',        'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
                [ 'name' => 'provider_id', 'type' => \Swoole\Table::TYPE_INT ],
            ],
        ],
        'rawEvents'     => [ //key format [lId:$leagueId:pId:$providerId:eventIdentifier:$eventIdentifier] = [id = $id, ...]
            'size'   => 102400,
            'column' => [
                [ 'name' => 'id',                 'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'league_id',          'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'event_identifier',   'type' => \Swoole\Table::TYPE_STRING, 'size' => 20 ],
                [ 'name' => 'sport_id',           'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'team_home_id',       'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'team_away_id',       'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'provider_id',        'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'reference_schedule', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
            ],
        ],
        'events'     => [ //key format [sId:$sportId:pId:$providerId:eId:$eventId] = [id = $id, ...]
            'size'   => 102400,
            'column' => [
                [ 'name' => 'id',                     'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'master_league_id',       'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'master_event_unique_id', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'sport_id',               'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'master_team_home_id',    'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'master_team_away_id',    'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'provider_id',            'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'reference_schedule',     'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'multi_league',           'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
            ],
        ],
        'rawEventMarkets' => [ //key format [lId:$leagueId:pId:$providerId:eId:$eventId] = [id = $id, ...]
            'size'   => 102400,
            'column' => [
                [ 'name' => 'id',             'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'league_id',      'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'event_id',       'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'odd_type_id',    'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'provider_id',    'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'odds',           'type' => \Swoole\Table::TYPE_FLOAT ],
                [ 'name' => 'odd_label',      'type' => \Swoole\Table::TYPE_STRING, 'size' => 10 ],
                [ 'name' => 'bet_identifier', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'is_main',        'type' => \Swoole\Table::TYPE_INT,    'size' => 1 ],
                [ 'name' => 'market_flag',    'type' => \Swoole\Table::TYPE_STRING, 'size' => 5 ],
            ],
        ],
        'eventMarkets'  => [ //key format [pId:$providerId:meUniqueId:$masterEventUniqueId:memUniqueId:$masterEventMarketUniqueId] = [id = $id, ...]
            'size' => 102400,
            'column' => [
                [ 'name' => 'id',                            'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'event_id',                      'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'odd_type_id',                   'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'master_event_market_unique_id', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
                [ 'name' => 'master_event_unique_id',        'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'event_market_id',               'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'provider_id',                   'type' => \Swoole\Table::TYPE_INT ],
                [ 'name' => 'odds',                          'type' => \Swoole\Table::TYPE_FLOAT ],
                [ 'name' => 'odd_label',                     'type' => \Swoole\Table::TYPE_STRING, 'size' => 10 ],
                [ 'name' => 'bet_identifier',                'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
                [ 'name' => 'is_main',                       'type' => \Swoole\Table::TYPE_INT,    'size' => 1 ],
                [ 'name' => 'market_flag',                   'type' => \Swoole\Table::TYPE_STRING, 'size' => 5 ],
            ],
        ],
        'transformed' => [
            'size'   => 102400,
            'column' => [ // key format [uid:$uid]
                [ 'name' => 'value', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 5000 ],
            ],
        ],
        /** PIVOT TABLES */
//        'event_team_links'    => [
//            'size'   => 102400,
//            'column' => [
//                [ 'name' => 'team_id',   'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'event_id',  'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'team_flag', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 4 ],
//            ],
//        ],
//        'master_event_links'  => [
//            'size' => 102400,
//            'column' => [
//                [ 'name' => 'event_id',            'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'event_match_link_id', 'type' => \Swoole\Table::TYPE_INT ],
//            ],
//        ],
//        'master_league_links' => [
//            'size'   => 102400,
//            'column' => [
//                [ 'name' => 'league_id',        'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'master_league_id', 'type' => \Swoole\Table::TYPE_INT ],
//            ],
//        ],
//        'master_team_links'   => [
//            'size' => 102400,
//            'column' => [
//                [ 'name' => 'team_id',        'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'master_team_id', 'type' => \Swoole\Table::TYPE_INT ],
//            ],
//        ],
        /** COLLECTED TABLES */
//        'event_markets' => [
//            'size'   => 102400,
//            'column' => [
//                [ 'name' => 'id',                      'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'master_events_unique_id', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
//                [ 'name' => 'odds_type_id',            'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'odds',                    'type' => \Swoole\Table::TYPE_FLOAT ],
//                [ 'name' => 'odd_label',               'type' => \Swoole\Table::TYPE_STRING, 'size' => 10 ],
//                [ 'name' => 'bet_identifier',          'type' => \Swoole\Table::TYPE_STRING, 'size' => 30 ],
//                [ 'name' => 'is_main',                 'type' => \Swoole\Table::TYPE_INT,    'size' => 1 ],
//                [ 'name' => 'market_flag',             'type' => \Swoole\Table::TYPE_STRING, 'size' => 5 ],
//            ],
//        ],
//        'master_events'  => [
//            'size' => 102400,
//            'column' => [
//                [ 'name' => 'id',                     'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'sport_id',               'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'master_event_unique_id', 'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'master_league_id',       'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'master_event_home_id',   'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'master_event_away_id',   'type' => \Swoole\Table::TYPE_INT ],
//            ],
//        ],
//        'master_leagues' => [
//            'size' => 102400,
//            'column' => [
//                [ 'name' => 'id',           'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'multi_league', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
//                [ 'name' => 'sport_id',     'type' => \Swoole\Table::TYPE_INT ],
//            ],
//        ],
//        'master_teams'   => [
//            'size' => 102400,
//            'column' => [
//                [ 'name' => 'id',             'type' => \Swoole\Table::TYPE_INT ],
//                [ 'name' => 'multi_teamname', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 100 ],
//            ],
//        ],
        /** /DATABASE TABLES */
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
        'task_ipc_mode'      => 2,
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
        'enable_coroutine'   => false,
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
