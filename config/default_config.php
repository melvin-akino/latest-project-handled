<?php

return [
    'general'                       => [
        'price_format'              => config('constants.price-format'),
        'price_format_user'         => 1,
        'timezone'                  => 234,
    ],
    'trade-page'                    => [
        'suggested'                 => false,
        'trade_background'          => false,
        'hide_comp_names_in_fav'    => false,
        'live_position_values'      => false,
        'hide_exchange_only'        => false,
        'trade_layout'              => config('constants.trade-layout'),
        'trade_layout_user'         => 1,
        'sort_event'                => config('constants.sort-event'),
        'sort_event_user'           => 1,
    ],
    'bet-slip'                      => [
        'use_equivalent_bets'       => false,
        'offers_on_exchanges'       => false,
        'adv_placement_opt'         => false,
        'bets_to_fav'               => false,
        'adv_betslip_info'          => false,
        'tint_bookies'              => false,
        'adaptive_selection'        => config('constants.betslip-adaptive-selection'),
        'adaptive_selection_user'   => 2,
    ],
    'bookies'                       => [],
    'bet-columns'                   => [],
    'notifications-and-sounds'      => [
        'bet_confirm'               => false,
        'site_notifications'        => false,
        'popup_notifications'       => false,
        'order_notifications'       => false,
        'event_sounds'              => false,
        'order_sounds'              => false,
    ],
    'language'                      => [
        'data'                      => [
            [
                'id'                => 1,
                'key'               => "en",
                'value'             => "English",
            ],
            [
                'id'                => 2,
                'key'               => "es",
                'value'             => "Spanish",
            ],
        ],
        'language_user'             => 1,
    ],
];