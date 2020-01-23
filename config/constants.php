<?php

return [
    'user-sport-odd-configuration'  => [
        [
            'sport_odd_type_id' => 1,
            'sport_id'          => 1, // SOCCER
            'sport'             => 'Soccer',
            'type'              => 'FT 1X2',
            'odd_type_id'       => 1, // FT 1X2
            'active'            => true
        ], [
            'sport_odd_type_id' => 2,
            'sport_id'          => 1, // SOCCER
            'sport'             => 'Soccer',
            'type'              => 'FT Handicap',
            'odd_type_id'       => 2, // FT Handicap
            'active'            => true
        ], [
            'sport_odd_type_id' => 3,
            'sport_id'          => 1, // SOCCER
            'sport'             => 'Soccer',
            'type'              => 'FT O/U',
            'odd_type_id'       => 3, // FT O/U
            'active'            => true
        ], [
            'sport_odd_type_id' => 4,
            'sport_id'          => 1, // SOCCER
            'sport'             => 'Soccer',
            'type'              => 'FT O/E',
            'odd_type_id'       => 4, // FT O/E
            'active'            => true
        ], [
            'sport_odd_type_id' => 5,
            'sport_id'          => 1, // SOCCER
            'sport'             => 'Soccer',
            'type'              => '1H 1X2',
            'odd_type_id'       => 5, // 1H 1X2
            'active'            => true
        ], [
            'sport_odd_type_id' => 6,
            'sport_id'          => 1, // SOCCER
            'sport'             => 'Soccer',
            'type'              => '1H Handicap',
            'odd_type_id'       => 6, // 1H Handicap
            'active'            => true
        ], [
            'sport_odd_type_id' => 7,
            'sport_id'          => 1, // SOCCER
            'sport'             => 'Soccer',
            'type'              => '1H O/U',
            'odd_type_id'       => 7, // 1H O/U
            'active'            => true
        ]
    ],
    'price-format'                  => [
        [
            'id'    => 1,
            'value' => 'Decimal',
        ],
        [
            'id'    => 2,
            'value' => 'HongKong',
        ],
        [
            'id'    => 3,
            'value' => 'United States',
        ],
        [
            'id'    => 4,
            'value' => 'Indo Odds',
        ],
    ],
    'trade-layout'                  => [
        [
            'id'    => 1,
            'value' => 'Asian',
        ],
        [
            'id'    => 2,
            'value' => 'European',
        ],
    ],
    'sort-event'                    => [
        [
            'id'    => 1,
            'value' => 'Competition Name',
        ],
        [
            'id'    => 2,
            'value' => 'Event Start Time',
        ],
    ],
    'betslip-adaptive-selection'    => [
        [
            'id'    => 1,
            'value' => "Never",
        ],
        [
            'id'    => 2,
            'value' => "Only bookies at placement",
        ],
        [
            'id'    => 3,
            'value' => "Add new bookies to order",
        ],
    ],
];