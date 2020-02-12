<?php

namespace App\Services;

use Hhxsv5\LaravelS\Swoole\Server as LaravelSwooleServer;

class Server extends LaravelSwooleServer
{
    protected function bindWebSocketEvent()
    {
        parent::bindWebSocketEvent();
        if ($this->enableWebSocket) {
            $eventHandler = function ($method, array $params) {
                $this->callWithCatchException(function () use ($method, $params) {
                    call_user_func_array([$this->getWebSocketHandler(), $method], $params);
                });
            };

            $this->swoole->on('Handshake', function () use ($eventHandler) {
                $eventHandler('onHandshake', func_get_args());
            });
        }
    }
}
