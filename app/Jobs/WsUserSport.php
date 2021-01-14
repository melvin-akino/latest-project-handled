<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WsUserSport implements ShouldQueue
{
    use Dispatchable;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function handle()
    {
        $server = app('swoole');
        $userSport = getUserDefault($this->userId, 'sport');
        $fd = $server->wsTable->get('uid:' . $this->userId);

        if ($server->isEstablished($fd['value'])) {
            $server->push($fd['value'], json_encode([
                'getUserSport' => [
                    'sport_id' => $userSport['default_sport']
                ]
            ]));

            $toLogs = [
                "class"       => "WsUserSport",
                "message"     => [
                    'getUserSport' => [
                        'sport_id' => $userSport['default_sport']
                ],
                "module"      => "JOB",
                "status_code" => 200,
            ];
            monitorLog('monitor_jobs', 'info', $toLogs);
        }
    }
}
