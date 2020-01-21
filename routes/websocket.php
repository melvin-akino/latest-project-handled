<?php


use Illuminate\Http\Request;
use SwooleTW\Http\Websocket\Facades\Websocket;

/*
|--------------------------------------------------------------------------
| Websocket Routes
|--------------------------------------------------------------------------
|
| Here is where you can register websocket events for your application.
|
*/

Websocket::on('connect', function ($websocket, Request $request) {

    for($i = 0; $i < 10000002; $i++) {
        $ft_1x2 = 0;
        if ($i % 5 == 1) {
            $ft_1x2 = 1.95;
        } else if ($i % 3 == 2) {
            $ft_1x2 = 2.23;
        } else  if ($i % 3 == 3) {
            $ft_1x2 = 4.86;
        }
        $websocket->emit('changeOdds', [
            [
                "uid" => "123b2jh1oio2b1jkb",
                "home_team_name" => "Los Angeles Lakers",
                "away_team_name" => "Los Angeles Clippers",
                "ft_1x2" => [
                    "home" => $ft_1x2,
                    "away" => 4.90,
                    "draw" => 5.24
                ],
                "ft_hdp" => [
                    "home" => 1.63,
                    "away" => 1.13
                ],
                "ft_ou" => [
                    "home" => 5.41,
                    "away" => 3.31
                ]
            ]
        ]);
    }
    // called while socket on connect
    $request->user();
    auth()->user();
});

Websocket::on('disconnect', function ($websocket) {
    // called while socket on disconnect
});

Websocket::on('example', function ($websocket, $data) {
    $websocket->emit('message', $data);
});

Websocket::on('changeOdds', function($websocket, $data) {
    $websocket->emit('changeOdds', "asd");
});
