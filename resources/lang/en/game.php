<?php

return [
    'bet'       => [
        'best-price' => [
            'continue' => "You still have remaining stakes from your previous Order.",
            'success'  => "Order has been Requested!",
        ],
        'fast-bet'   => [
            'continue' => "You still have remaining stakes from your previous Order.",
            'success'  => "Order has been Requested!",
        ],
        'errors'     => [
            'wallet_not_found'      => "User wallet not found",
            'insufficient'          => "Insufficient wallet balance",
            'no_bookmaker'          => "Bookmaker not found",
            'type_has_been_changed' => ":type has been changed. Please refresh the bet slip",
            'place-bet-event-ended' => "Match already ended. Please close this Bet Slip.",
            'not-enough-min-stake'  => "Stake lower than minimum stake or cannot proceed to next provider.",
        ],
    ],
    'bet_slip_logs' => [
        'order_placed' => "Order Placed",
        'price_update' => "Market Updated",
    ],
    'watchlist' => [
        'added'     => "Game Successfully Added to your Watchlist",
        'removed'   => "Game Successfully Removed to your Watchlist",
        'failed'    => "Something went wrong. Please try again.",
        'not-found' => "Sorry, that game does not exist anymore.",
    ],
    'wallet-api' => [
        'error' => [
            'user' => "Oops! Something went wrong.<br>Please contact support support@multline.io, or try refreshing the page.",
            'prov' => "Ooops! Something went wrong.<br>Please contact support support@multline.io, or try refreshing the page.",
        ],
    ],
];
