<?php

/*
|--------------------------------------------------------------------------
| Custom Helper Functions
|--------------------------------------------------------------------------
|
| Here you can create your own functions depending on your own needs
| and call them anywhere inside your Laravel Project.
|
| Some of the functions created here can still be extended to
| accomodate every developer's needs.
|
| MUST require every developer to include a commented @author tag
| for us to know which one to look for in case of any questions
| and/or misunderstandings. Also, write down developer's name
| should there be any adjustments made with existing helper functions.
|
| Have fun coding!
|
*/

use App\Models\{Sport, UserConfiguration};

use Illuminate\Support\Facades\Cookie;
use RdKafka\Conf as KafkaConf;

/**
 * Delete Cookie by Name
 *
 * @param   string   $cookieName     Illuminate\Support\Facades\Cookie;
 *
 * @author  Kevin Uy
 */
function deleteCookie(string $cookieName)
{
    Cookie::queue(Cookie::forget($cookieName));
}

/**
 * Save Authenticated User's Default Configuration by Type
 *
 * @param   int        $userId
 * @param   string     $type
 * @param   array|null $data
 * @return  json
 *
 * @author  Kevin Uy, Alex Virtucio
 */
if (!function_exists('setUserDefault')) {
    function setUserDefault(int $userId, string $type, array $data = null)
    {
        $data = [];
        $types = [
            'sport',
            'league',
        ];

        if (in_array($type, $types)) {
            switch ($type) {
                case 'sport':
                    UserConfiguration::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'type'    => "DEFAULT_SPORT",
                            'menu'    => 'TRADE',
                        ],
                        [
                            'value' => $data->sport_id
                        ]
                    );
                break;

                case 'league':
                    //
                break;

                $data = [
                    'status'  => true,
                    'message' => trans('notifications.save.success')
                ];
            }
        } else {
            $data = [
                'status' => false,
                'error'  => trans('generic.bad-request'),
            ];
        }

        return $data;
    }
}

/**
 * Get Authenticated User's Default Configuration by Type
 *
 * @param   int    $userId
 * @param   string $type
 * @return  $data
 *
 * @author  Kevin Uy
 */
if (!function_exists('getUserDefault')) {
    function getUserDefault(int $userId, string $type)
    {
        $data = [];
        $types = [
            'sport',
            'league',
        ];

        if (in_array($type, $types)) {
            switch ($type) {
                case 'sport':
                    $defaultSport = UserConfiguration::where('type', 'DEFAULT_SPORT')
                        ->where('menu', 'TRADE')
                        ->where('user_id', $userId);

                    if ($defaultSport->count() == 0) {
                        $defaultSport = Sport::getActiveSports();
                    }

                    $data = [
                        'status'        => true,
                        'default_sport' => $defaultSport->first()->id,
                    ];
                break;

                case 'league':
                    //
                break;
            }
        } else {
            $data = [
                'status' => false,
                'error'  => trans('generic.bad-request'),
            ];
        }

        return $data;
    }
}

/**
 * Broadcast Emit
 *
 * @params array $content
 *
 * @author Alex Virtucio
 */
if (!function_exists('wsEmit')) {
    function wsEmit($content)
    {
        $server = app('swoole');
        $table = $server->wsTable;
        foreach ($table as $key => $row) {
            if (strpos($key, 'uid:') === 0 && $server->isEstablished($row['value'])) {
                $server->push($row['value'], json_encode($content));
            }
        }
    }
}
