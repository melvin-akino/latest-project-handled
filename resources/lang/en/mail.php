<?php

return [
    'password'                      => [
        'request'                   => [
            'subject'               => "MULTILINE IO : Password Reset Request",
            'body'                  => "You recently reqested to reset your password for your &nbsp; <a href=\"#\">multiline.io</a> &nbsp; account. Click the button below to reset it.",
            'reset-button'          => "Reset your Password",
            'footer'                => "If you did not request a password reset, please ignore this email or reply to let us know. This password reset is only valid for the next 30 minutes.",
        ],
        'reset' => [
            'subject'               => "MULTILINE IO : Password Reset Success",
            'success'               => "You have recently updated your password for the following account:",
            'body'                  => "If you did change your password, no further action is required.",
            'footer'                => "If you did not request a password reset, please reply to this email to let us know.",
        ],
    ],
    'registration'                  => [
        'subject'                   => "MULTILINE IO : Registration",
        'intro'                     => "Welcome to <span class=\"font-bold text-orange\">MULTILINE.IO</span> !",
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
    'footer-rights'                 => "All Rights Reserved 2020 &copy; Multiline IO",
    'remarks'                       => "Thanks,<br /><a href=\"#\">multiline.io</a>",
];