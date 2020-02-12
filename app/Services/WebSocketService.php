<?php

namespace App\Services;

use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use League\OAuth2\Server\ResourceServer;
use Laravel\Passport\Guards\TokenGuard;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\ClientRepository;
use Illuminate\Http\Request as HttpRequest;

class WebSocketService implements WebSocketHandlerInterface
{
    public function __construct()
    {
    }

    public function onOpen(Server $server, Request $request)
    {
        $a = $server->kafkaTable->get('message:565');


        $user = $this->getUser($request->get['token']);

        $server->push($request->fd, json_encode(['changeOdds' => ['user' => $user['id'], 'request' => $request->get]]));
        $server->push($request->fd, json_encode([$a, Auth::user()]));
        $server->push($request->fd, 'Welcome to LaravelS');


    }

    public function onMessage(Server $server, Frame $frame)
    {
        Log::error("SDfsdf");
        var_dump("SDfsdf");
//        Log::error($frame);
        $server->push($frame->fd, date('Y-m-d H:i:s'));
    }

    public function onClose(Server $server, $fd, $reactorId)
    {
    }

    function getUser($bearerToken) {
        $tokenguard = new TokenGuard(
            resolve(ResourceServer::class),
            Auth::createUserProvider('users'),
            resolve(TokenRepository::class),
            resolve(ClientRepository::class),
            resolve('encrypter')
        );
        $request = HttpRequest::create('/');
        $request->headers->set('Authorization', 'Bearer ' . $bearerToken);
        return $tokenguard->user($request);
    }
}
