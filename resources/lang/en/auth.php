<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed'            => 'These credentials do not match our records.',
    'login'             => [
        '401'           => "Unauthorized.",
        'success'       => "Login Successful",
    ],
    'logout'            => [
        'success'       => "Logout Successful",
    ],
    'password_reset'    => [
        'email'         => [
            '404'       => "E-mail Address not found",
            'sent'      => "Password Reset Link Sent to your E-mail Address",
        ],
        'token'         => [
            '404'       => "Invalid Password Reset Token",
        ],
        'success'       => "Password Reset Successful",
    ],
    'register'          => [
        'success'       => "User Successfully Registered",
    ],
    'throttle'          => 'Too many login attempts. Please try again in :seconds seconds.',
];
