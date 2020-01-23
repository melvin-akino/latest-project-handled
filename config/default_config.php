<?php

return [
    'general'                       => [
        'price_formats'             => config('constants.price-format'),
        'price_format'              => 1,
        'timezone'                  => 234,
    ],
    'trade-page'                    => [
        'suggested'                 => false,
        'trade_background'          => false,
        'hide_comp_names_in_fav'    => false,
        'live_position_values'      => false,
        'hide_exchange_only'        => false,
        'trade_layouts'             => config('constants.trade-layout'),
        'trade_layout'              => 1,
        'sort_events'               => config('constants.sort-event'),
        'sort_event'                => 1,
    ],
    'bet-slip'                      => [
        'use_equivalent_bets'       => false,
        'offers_on_exchanges'       => false,
        'adv_placement_opt'         => false,
        'bets_to_fav'               => false,
        'adv_betslip_info'          => false,
        'tint_bookies'              => false,
        'adaptive_selections'       => config('constants.betslip-adaptive-selection'),
        'adaptive_selection'        => 2,
    ],
    'bookies'                       => [
        'disabled_bookies'          => []
    ],
    'bet-columns'                   => [
        'disabled_columns'          => []
    ],
    'notifications-and-sounds'      => [
        'bet_confirm'               => false,
        'site_notifications'        => false,
        'popup_notifications'       => false,
        'order_notifications'       => false,
        'event_sounds'              => false,
        'order_sounds'              => false,
    ],
    'language'                      => [
        'languages'                 => [
            [
                'id'                => 1,
                'key'               => "en",
                'value'             => "English",
            ], [
                'id'                => 2,
                'key'               => "es",
                'value'             => "Spanish",
            ],
        ],
        'language'             => 1,
    ],
];
