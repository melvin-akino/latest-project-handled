<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scraping timing system config 
    |--------------------------------------------------------------------------
    |
    |
    */
    'schedule_mapping' => [
        'inplay' => [
            'SCHEDULE_INPLAY_TIMER',
            'INTERVAL_REQ_PER_EXEC_INPLAY',
            'NUM_OF_REQ_PER_EXECUTION_INPLAY',
        ],
        'today' => [
            'SCHEDULE_TODAY_TIMER',
            'INTERVAL_REQ_PER_EXEC_TODAY',
            'NUM_OF_REQ_PER_EXECUTION_TODAY',
        ],
        'early' => [
            'SCHEDULE_EARLY_TIMER',
            'INTERVAL_REQ_PER_EXEC_EARLY',
            'NUM_OF_REQ_PER_EXECUTION_EARLY',
        ],
    ],

    'schedule_mapping_field' => [
        'SCHEDULE_INPLAY_TIMER' => 'timer',
        'INTERVAL_REQ_PER_EXEC_INPLAY' => 'requestInterval',
        'NUM_OF_REQ_PER_EXECUTION_INPLAY' => 'requestNumber',
        'SCHEDULE_TODAY_TIMER' => 'timer',
        'INTERVAL_REQ_PER_EXEC_TODAY' => 'requestInterval',
        'NUM_OF_REQ_PER_EXECUTION_TODAY' => 'requestNumber',
        'SCHEDULE_EARLY_TIMER' => 'timer',
        'INTERVAL_REQ_PER_EXEC_EARLY' => 'requestInterval',
        'NUM_OF_REQ_PER_EXECUTION_EARLY' => 'requestNumber',
    ],

    'refresh_db_interval' => 60 * 10,

];
