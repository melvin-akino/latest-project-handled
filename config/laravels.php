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
        'enable' => true,
        'handler' => \App\Services\WebSocketService::class,
    ],
    'sockets'                  => [],
    'processes'                => [
        'kafka_consume' => [
            'class'    => \App\Processes\KafkaConsume::class,
            'redirect' => false,
            'pipe'     => 0,
            'enable'   => true
        ]
    ],
    'timer'                    => [
        'enable'        => env('LARAVELS_TIMER', true),
        'jobs'          => [
            // Enable LaravelScheduleJob to run `php artisan schedule:run` every 1 minute, replace Linux Crontab
            //\Hhxsv5\LaravelS\Illuminate\LaravelScheduleJob::class,
            // Two ways to configure parameters:
            // [\App\Jobs\XxxCronJob::class, [1000, true]], // Pass in parameters when registering
            // \App\Jobs\XxxCronJob::class, // Override the corresponding method to return the configuration
            \App\Jobs\ScrapeInPlayRequest::class,
            \App\Jobs\ScrapeTodayRequest::class,
            \App\Jobs\ScrapeEarlyRequest::class,
        ],
        'max_wait_time' => 5,
    ],
    'events'                   => [],
    'swoole_tables'            => [
        'ws' => [// The Key is table name, will add suffix "Table" to avoid naming conflicts. Here defined a table named "wsTable"
             'size'   => 102400,// The max size
             'column' => [// Define the columns
                  ['name' => 'value', 'type' => \Swoole\Table::TYPE_INT, 'size' => 8],
             ],
        ],
        'indexes'    => [// The Key is table name, will add suffix "Table" to avoid naming conflicts. Here defined a table named "wsTable"
             'size'   => 102400,// The max size
             'column' => [// Define the columns
                  ['name' => 'value', 'type' => \Swoole\Table::TYPE_INT, 'size' => 100],
             ],
        ],
        'events'     => [// The Key is table name, will add suffix "Table" to avoid naming conflicts. Here defined a table named "wsTable"
             'size'   => 102400,// The max size
             'column' => [// Define the columns
                  ['name' => 'uid',         'type' => \Swoole\Table::TYPE_STRING, 'size' => 30],
                  ['name' => 'timestamp',   'type' => \Swoole\Table::TYPE_STRING, 'size' => 15],
                  ['name' => 'payload',     'type' => \Swoole\Table::TYPE_STRING, 'size' => 5000],
             ],
        ],
        'clients'    => [// The Key is table name, will add suffix "Table" to avoid naming conflicts. Here defined a table named "wsTable"
             'size'   => 102400,// The max size
             'column' => [// Define the columns
                  ['name' => 'sid',     'type' => \Swoole\Table::TYPE_STRING, 'size' => 100],
                  ['name' => 'user_id', 'type' => \Swoole\Table::TYPE_INT, 'size' => 999999],
             ],
        ],
        'rooms'      => [// The Key is table name, will add suffix "Table" to avoid naming conflicts. Here defined a table named "wsTable"
             'size'   => 102400,// The max size
             'column' => [// Define the columns
                  ['name' => 'room_name', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 100],
             ],
        ],
        'clientRoom' => [// The Key is table name, will add suffix "Table" to avoid naming conflicts. Here defined a table named "wsTable"
             'size'   => 102400,// The max size
             'column' => [// Define the columns
                  ['name' => 'sid',         'type' => \Swoole\Table::TYPE_STRING, 'size' => 100],
                  ['name' => 'room_name',   'type' => \Swoole\Table::TYPE_STRING, 'size' => 100],
             ],
        ],
        'leagues'    => [// The Key is table name, will add suffix "Table" to avoid naming conflicts. Here defined a table named "wsTable"
             'size'   => 102400,// The max size
             'column' => [// Define the columns
                  ['name' => 'id',   'type' => \Swoole\Table::TYPE_INT],
                  ['name' => 'sport_id',    'type' => \Swoole\Table::TYPE_INT],
                  ['name' => 'provider_id', 'type' => \Swoole\Table::TYPE_INT],
                  ['name' => 'multi_league',      'type' => \Swoole\Table::TYPE_STRING, 'size' => 100],
             ],
        ],
        'providers' => [// The Key is table name, will add suffix "Table" to avoid naming conflicts. Here defined a table named "wsTable"
            'size'   => 500,// The max size
            'column' => [// Define the columns
                         ['name' => 'id',                'type' => \Swoole\Table::TYPE_INT],
//                         ['name' => 'name',              'type' => \Swoole\Table::TYPE_STRING, 'size' => 30],
                         ['name' => 'alias',             'type' => \Swoole\Table::TYPE_STRING, 'size' => 10],
//                         ['name' => 'punter_percentage', 'type' => \Swoole\Table::TYPE_INT],
//                         ['name' => 'priority',          'type' => \Swoole\Table::TYPE_INT],
//                         ['name' => 'is_enabled',        'type' => \Swoole\Table::TYPE_STRING, 'size' => 5],
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
        'reload_async'       => false,
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
