<?php

return [
    'bet'       => [
        'best-price' => [
            'continue' => "You still have remaining stakes from your previous Order.",
            'success'  => "Order Successfully Placed!",
        ],
        'fast-bet'   => [
            'continue' => "You still have remaining stakes from your previous Order.",
            'success'  => "Order Successfully Placed!",
        ],
        'errors'     => [
            'wallet_not_found'      => "User wallet not found",
            'insufficient'          => "Insufficient wallet balance",
            'no_bookmaker'          => "Bookmaker not found",
            'type_has_been_changed' => ":type has been changed. Please refresh the bet slip"
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
];
