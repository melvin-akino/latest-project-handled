<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password Reset Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are the default lines which match reasons
    | that are given by the password broker for a password update attempt
    | has failed, such as for an invalid token or invalid new password.
    |
    */

    'reset'         => 'Your password has been reset!',
    'sent'          => 'We have e-mailed your password reset link!',
    'throttled'     => 'Please wait before retrying.',
    'token'         => 'This password reset token is invalid.',
    'user'          => "We can't find a user with that e-mail address.",
    'current'       => [
        'success'   => "",
        'incorrect' => "Old Password is incorrect.",
    ],
    'change'        => [
        'success'   => "",
        'unique'    => "You cannot use your Old Password as your New Password.",
    ],

];
