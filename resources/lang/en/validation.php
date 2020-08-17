<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'                      => 'The :attribute must be accepted.',
    'active_url'                    => 'The :attribute is not a valid URL.',
    'after'                         => 'The :attribute must be a date after :date.',
    'after_or_equal'                => 'The :attribute must be a date after or equal to :date.',
    'alpha'                         => 'The :attribute may only contain letters.',
    'alpha_dash'                    => 'The :attribute may only contain letters, numbers, dashes and underscores.',
    'alpha_num'                     => 'The :attribute may only contain letters and numbers.',
    'array'                         => 'The :attribute must be an array.',
    'before'                        => 'The :attribute must be a date before :date.',
    'before_or_equal'               => 'The :attribute must be a date before or equal to :date.',
    'between'                       => [
        'numeric'                   => 'The :attribute must be between :min and :max.',
        'file'                      => 'The :attribute must be between :min and :max kilobytes.',
        'string'                    => 'The :attribute must be between :min and :max characters.',
        'array'                     => 'The :attribute must have between :min and :max items.',
    ],
    'boolean'                       => 'The :attribute field must be true or false.',
    'confirmed'                     => 'The :attribute confirmation does not match.',
    'date'                          => 'The :attribute is not a valid date.',
    'date_equals'                   => 'The :attribute must be a date equal to :date.',
    'date_format'                   => 'The :attribute does not match the format :format.',
    'different'                     => 'The :attribute and :other must be different.',
    'digits'                        => 'The :attribute must be :digits digits.',
    'digits_between'                => 'The :attribute must be between :min and :max digits.',
    'dimensions'                    => 'The :attribute has invalid image dimensions.',
    'distinct'                      => 'The :attribute field has a duplicate value.',
    'email'                         => 'The :attribute must be a valid email address.',
    'ends_with'                     => 'The :attribute must end with one of the following: :values',
    'exists'                        => 'The selected :attribute is invalid.',
    'file'                          => 'The :attribute must be a file.',
    'filled'                        => 'The :attribute field must have a value.',
    'gt'                            => [
        'numeric'                   => 'The :attribute must be greater than :value.',
        'file'                      => 'The :attribute must be greater than :value kilobytes.',
        'string'                    => 'The :attribute must be greater than :value characters.',
        'array'                     => 'The :attribute must have more than :value items.',
    ],
    'gte'                           => [
        'numeric'                   => 'The :attribute must be greater than or equal :value.',
        'file'                      => 'The :attribute must be greater than or equal :value kilobytes.',
        'string'                    => 'The :attribute must be greater than or equal :value characters.',
        'array'                     => 'The :attribute must have :value items or more.',
    ],
    'image'                         => 'The :attribute must be an image.',
    'in'                            => 'The selected :attribute is invalid.',
    'in_array'                      => 'The :attribute field does not exist in :other.',
    'integer'                       => 'The :attribute must be an integer.',
    'ip'                            => 'The :attribute must be a valid IP address.',
    'ipv4'                          => 'The :attribute must be a valid IPv4 address.',
    'ipv6'                          => 'The :attribute must be a valid IPv6 address.',
    'json'                          => 'The :attribute must be a valid JSON string.',
    'lt'                            => [
        'numeric'                   => 'The :attribute must be less than :value.',
        'file'                      => 'The :attribute must be less than :value kilobytes.',
        'string'                    => 'The :attribute must be less than :value characters.',
        'array'                     => 'The :attribute must have less than :value items.',
    ],
    'lte'                           => [
        'numeric'                   => 'The :attribute must be less than or equal :value.',
        'file'                      => 'The :attribute must be less than or equal :value kilobytes.',
        'string'                    => 'The :attribute must be less than or equal :value characters.',
        'array'                     => 'The :attribute must not have more than :value items.',
    ],
    'max'                           => [
        'numeric'                   => 'The :attribute may not be greater than :max.',
        'file'                      => 'The :attribute may not be greater than :max kilobytes.',
        'string'                    => 'The :attribute may not be greater than :max characters.',
        'array'                     => 'The :attribute may not have more than :max items.',
    ],
    'mimes'                         => 'The :attribute must be a file of type: :values.',
    'mimetypes'                     => 'The :attribute must be a file of type: :values.',
    'min'                           => [
        'numeric'                   => 'The :attribute must be at least :min.',
        'file'                      => 'The :attribute must be at least :min kilobytes.',
        'string'                    => 'The :attribute must be at least :min characters.',
        'array'                     => 'The :attribute must have at least :min items.',
    ],
    'not_in'                        => 'The selected :attribute is invalid.',
    'not_regex'                     => 'The :attribute format is invalid.',
    'numeric'                       => 'The :attribute must be a number.',
    'password'                      => 'The password is incorrect.',
    'present'                       => 'The :attribute field must be present.',
    'regex'                         => 'The :attribute format is invalid.',
    'required'                      => 'The :attribute field is required.',
    'required_if'                   => 'The :attribute field is required when :other is :value.',
    'required_unless'               => 'The :attribute field is required unless :other is in :values.',
    'required_with'                 => 'The :attribute field is required when :values is present.',
    'required_with_all'             => 'The :attribute field is required when :values are present.',
    'required_without'              => 'The :attribute field is required when :values is not present.',
    'required_without_all'          => 'The :attribute field is required when none of :values are present.',
    'same'                          => 'The :attribute and :other must match.',
    'size'                          => [
        'numeric'                   => 'The :attribute must be :size.',
        'file'                      => 'The :attribute must be :size kilobytes.',
        'string'                    => 'The :attribute must be :size characters.',
        'array'                     => 'The :attribute must contain :size items.',
    ],
    'starts_with'                   => 'The :attribute must start with one of the following: :values',
    'string'                        => 'The :attribute must be a string.',
    'timezone'                      => 'The :attribute must be a valid zone.',
    'unique'                        => 'The :attribute has already been taken.',
    'uploaded'                      => 'The :attribute failed to upload.',
    'url'                           => 'The :attribute format is invalid.',
    'uuid'                          => 'The :attribute must be a valid UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name'        => [
            'rule-name'         => 'custom-message',
        ],
        'date'                  => "Invalid input. Field must be a valid date format.",
        'email'                 => [
            'exists'            => ":attribute does not exists.",
            'required'          => ":attribute is required.",
            'unique'            => ":attribute already exists.",
            'valid'             => "Please input a valid :attribute format.",
        ],
        'error'                 => "The given data is invalid.",
        'exists'                => "Invalid input. Entry does not exist from our records.",
        'name'                  => [
            'min'               => ":attribute must be at least :count characters.",
            'max'               => ":attribute is only up to :count characters.",
            'unique'            => ":attribute already exists.",
            'alphanumeric'      => ":attribute only accepts alphanumeric characters (A-Z, a-z, 0-9).",
        ],
        'numeric'               => "Invalid input. Field must be a number.",
        'password'              => [
            'min'               => ":attribute must be at least :count characters.",
            'max'               => ":attribute is only up to :count characters.",
        ],
        'password_confirmation' => [
            'min'               => ":attribute must be at least :count characters.",
            'max'               => ":attribute is only up to :count characters.",
            'same'              => ":attribute must be the same with Password.",
            'new-same'          => ":attribute must be the same as the New Password.",
        ],
        'remember_me'           => [
            'boolean'           => "Invalid input.",
        ],
        'required'              => "This field is required.",
        'string'                => "Invalid input. Field must be a string.",
        'phone'                 => [
            'regex'             => "Invalid number format",
            'min'               => "The phone must be at least :min digits"
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'date'                      => "Date",
        'datetime'                  => "Datetime",
        'ip_address'                => "IP Address",
        'email'                     => "E-mail Address",
        'name'                      => "Username",
        'password'                  => "Password",
        'password_confirmation'     => "Confirm Password",
        'postcode'                  => "Postal Code",
        // GENERAL
        'price_format'              => "Price Format",
        'timezone'                  => "Timezone",
        // TRADE PAGE
        'suggested'                 => "Suggested Competitions/Events",
        'trade_background'          => "Trade Background",
        'hide_comp_names_in_fav'    => "Hide Competition Names in Favorites",
        'live_position_values'      => "Live Position Values",
        'hide_exchange_only'        => "Hide Exchange-only lines",
        'trade_layout'              => "Trade Layout",
        'sort_event'                => "Sort Events",
        // BET SLIP
        'use_equivalent_bets'       => "Use Equivalent Bets",
        'offers_on_exchanges'       => "Put Offers on Exnchanges",
        'adv_placement_opt'         => "Show Advance placement Options",
        'bets_to_fav'               => "Add Bets to Favorite Events",
        'adv_betslip_info'          => "Show Advance Bet Slip Information",
        'tint_bookies'              => "Tint Bookies",
        'adaptive_selection'        => "Adaptive Selection",
        // NOTIFICATIONS AND SOUNDS
        'bet_confirm'               => "Bet Placement Confirmation",
        'site_notifications'        => "Show Website Notifications",
        'popup_notifications'       => "Popup Event Notifications",
        'order_notifications'       => "Popup Order Notifications",
        'event_sounds'              => "Bet Event Sounds",
        'order_sounds'              => "Play Order Status Sounds",
        // LANGUAGE
        'language'                  => "Language",
    ],

];
