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

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\{Sport, UserConfiguration};

use Illuminate\Support\Facades\Cookie;
use RdKafka\Conf as KafkaConf;

/* Datatable for CRM admin */

function dataTable(Request $request, $query, $cols = null)
{
    $order = collect($request->input('order')[0]);
    $col = collect($request->input('columns')[$order->get('column')])->get('data');
    $dir = $order->get('dir');

    $q = trim($request->input('search')['value']);
    $len = $request->input('length');
    $page = ($request->input('start') / $len) + 1;

    Paginator::currentPageResolver(function () use ($page) {
        return $page;
    });

    $pagin = null;

    if (!empty($q)) {
        $pagin = $query->search($q, $cols)->orderBy($col, $dir)->paginate($len);
    } else {
        $pagin = $query->orderBy($col, $dir)->paginate($len);
    }

    return response()->json([
        "draw" => intval($request->input('draw')),
        "recordsTotal" => $pagin->total(),
        "recordsFiltered" => $pagin->total(),
        "data" => $pagin->items()
    ]);
}
/* end databtable */

/* Swal CRM popup container*/

function swal($title, $html, $type)
{
    $swal = [
        'title' => $title,
        'html' => $html,
        'type' => $type
    ];

    return compact('swal');
}
/* End SWal CRM popup container */

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
    function setUserDefault(int $userId, string $type, array $data = [])
    {
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
                            'value' => $data['sport_id']
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
            'sort-event'
        ];

        if (in_array($type, $types)) {
            switch ($type) {
                case 'sport':
                    $defaultSport = UserConfiguration::where('type', 'DEFAULT_SPORT')
                        ->where('menu', 'TRADE')
                        ->where('user_id', $userId);

                    if ($defaultSport->count() == 0) {
                        $defaultSport = Sport::getActiveSports();
                        $sport = $defaultSport->first()->id;
                    } else {
                        $sport = $defaultSport->first()->value;
                    }

                    $data = [
                        'status'        => true,
                        'default_sport' => $sport,
                    ];
                break;
                case 'sort-event':
                    $defaultEventSort = UserConfiguration::where('type', 'sort_event')
                        ->where('menu', 'trade-page')
                        ->where('user_id', $userId);

                    if ($defaultEventSort->count() == 0) {
                        $sort = config('default_config.trade-page.sort_event');
                    } else {
                        $sort = $defaultEventSort->first()->value;
                    }

                    $data = [
                        'status'        => true,
                        'default_sort'  => $sort,
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

if (!function_exists('wsEmit')) {
    function wsEmit($content)
    {
        $server = app('swoole');
        $table = $server->wsTable;
        foreach ($table as $key => $row) {
            if (strpos($key, 'uid:') === 0 && $server->isEstablished($row['value'])) {
//                $content = sprintf('Broadcast: new message "%s" from #%d', $frame->data, $frame->fd);
                $server->push($row['value'], json_encode($content));
            }
        }
    }
}


