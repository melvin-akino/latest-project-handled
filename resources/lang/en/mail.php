<?php

return [
    'password' => [
        'request' => [
            'body' => "You are receiving this email because we received a password reset request for your account.",
            'footer' => "If you did not request a password reset, no further action is required.",
        ],
        'reset' => [
            'success' => "You have changed your password successfully.",
            'body' => "If you did change your password, no further action is required.",
            'footer' => "If you did not change your password, protect your account.",
        ],
    ],
];