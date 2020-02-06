<?php

return [
    'password'                      => [
        'request'                   => [
            'body'                  => "You are receiving this email because we received a password reset request for your account.",
            'footer'                => "If you did not request a password reset, no further action is required.",
        ],
        'reset' => [
            'success'               => "You have changed your password successfully.",
            'body'                  => "If you did change your password, no further action is required.",
            'footer'                => "If you did not change your password, protect your account.",
        ],
    ],
    'registration'                  => [
        'header'                    => "We offer you a genuinely unique online sports betting experience with a single click!",
        'simultaneous-execution'    => [
            'title'                 => "Simultaneous Execution",
            'content'               => "Multiline.io brings you real-time odds offered by multiple bookmakers and exchanges. Orders are matched simultaneously at all available prices that satisfy your requirements.",
        ],
        'global-liquidity'          => [
            'title'                 => "Global Liquidity",
            'content'               => "Multiline.io is integrated with many of the world’s biggest bookmakers and betting exchanges. This includes Singbet, ISN, PIN, ISC, SBC, and SBO.",
        ],
        'market-based-prices'       => [
            'title'                 => "Market-based Prices",
            'content'               => "Orders are matched in price descending order. ‘Top down execution’ ensures that you always get the best price available in the market.",
        ],
        'comprehensive-coverage'    => [
            'title'                 => "Comprehensive Coverage",
            'content'               => "Early market, game day and in-running offers on Asian Handicaps and all other major markets for Football, Basketball, American Football, Baseball, and E-Sports.",
        ],
        'speed'                     => [
            'title'                 => "Speed",
            'content'               => "Multiline.io is the fastest sports betting software of its kind. We continually invest in the latest technologies to deliver the fastest price retrieval and bet placement in the market.",
        ],
        'footer'                    => "Thank you for using our application!",
    ],
];