<?php

define("ODD_FT1X1", 1);
define("ODD_FTHANDICAP", 2);
define("ODD_FTOVERUNDER", 3);
define("ODD_FTODDEVEN", 4);
define("ODD_ONEH1X2", 5);
define("ODD_ONEHHANDICAP", 6);
define("ODD_ONEHOVERUNDER", 7);

define("SPORT_SOCCER", 1);

return [
    'user_sport_odd_configurations' => [
        [
            'sport_id'    => SPORT_SOCCER,
            'odd_type_id' => ODD_FT1X1
        ], [
            'sport_id'    => SPORT_SOCCER,
            'odd_type_id' => ODD_FTHANDICAP
        ], [
            'sport_id'    => SPORT_SOCCER,
            'odd_type_id' => ODD_FTOVERUNDER
        ], [
            'sport_id'    => SPORT_SOCCER,
            'odd_type_id' => ODD_FTODDEVEN
        ], [
            'sport_id'    => SPORT_SOCCER,
            'odd_type_id' => ODD_ONEH1X2
        ], [
            'sport_id'    => SPORT_SOCCER,
            'odd_type_id' => ODD_ONEHHANDICAP
        ], [
            'sport_id'    => SPORT_SOCCER,
            'odd_type_id' => ODD_ONEHOVERUNDER
        ]
    ]
];
