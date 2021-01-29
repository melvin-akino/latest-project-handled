<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'kafkalog' => [
            'driver' => 'single',
            'path' => '/tmp/consumer.logs',
        ],

        'kafkaproducelog' => [
            'driver' => 'single',
            'path' => '/tmp/producer.logs',
        ],

        'scraping-odds' => [
            'driver' => 'single',
            'path'   => storage_path('logs/laravels-odds.log'),
            'level'  => 'debug',
        ],

        'scraping-events' => [
            'driver' => 'single',
            'path'   => storage_path('logs/laravels-events.log'),
            'level'  => 'debug',
        ],

        'scraping-leagues' => [
            'driver' => 'single',
            'path'   => storage_path('logs/laravels-leagues.log'),
            'level'  => 'debug',
        ],

        'bets' => [
            'driver' => 'single',
            'path'   => storage_path('logs/bets.log'),
            'level'  => 'debug',
        ],

        // LOGS MONITORING
        'monitor_bet_info' => [
            'driver' => 'single',
            'path'   => storage_path('logs/monitor/bet_info/laravel.log'),
            'level'  => 'debug',
        ],

        'monitor_jobs' => [
            'driver' => 'single',
            'path'   => storage_path('logs/monitor/jobs/laravel.log'),
            'level'  => 'debug',
        ],

        'monitor_tasks' => [
            'driver' => 'single',
            'path'   => storage_path('logs/monitor/tasks/laravel.log'),
            'level'  => 'debug',
        ],

        'monitor_ws' => [
            'driver' => 'single',
            'path'   => storage_path('logs/monitor/ws/laravel.log'),
            'level'  => 'debug',
        ],

        'monitor_handlers' => [
            'driver' => 'single',
            'path'   => storage_path('logs/monitor/handlers/laravel.log'),
            'level'  => 'debug',
        ],

        'monitor_process' => [
            'driver' => 'single',
            'path'   => storage_path('logs/monitor/process/laravel.log'),
            'level'  => 'debug',
        ],

        'monitor_api' => [
            'driver' => 'single',
            'path'   => storage_path('logs/monitor/api/laravel.log'),
            'level'  => 'debug',
        ],
    ],
];