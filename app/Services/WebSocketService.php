<?php

namespace App\Services;

use App\Jobs\WsLeagues;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Request;
use Swoole\Http\Response;
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
        $this->wsTable = app('swoole')->wsTable;
    }

    public function onOpen(Server $server, Request $request)
    {
//        $a = $server->kafkaTable->get('message:565');
//
//
        $user = $this->getUser($request->get['token']);
        $userId = $user ? $user['id'] : 0;

//        $server->kafkaTable->set('testing', )
//        $server->push($request->fd, json_encode(['changeOdds' => ['user' => $user['id'], 'request' => $request->get]]));
//        $server->push($request->fd, json_encode([$a, Auth::user()]));

        $this->wsTable->set('uid:' . $userId, ['value' => $request->fd]);// Bind map uid to fd
        $this->wsTable->set('fd:' . $request->fd, ['value' => $userId]);// Bind map fd to uid
        $server->push($request->fd, "Welcome to LaravelS #{$request->fd}");


    }

    public function onMessage(Server $server, Frame $frame)
    {
        $commands = [
            'getEarlyLeagues' => '\App\Jobs\WsLeagues',
        ];
        $commandFound = false;
        foreach ($commands as $key => $value) {
            $clientCommand = explode('_', $frame->data);
            if ($clientCommand[0] == $key) {
                $commandFound = true;
                $job = $commands[$clientCommand[0]];
                if (count($clientCommand) > 0) {
                    $job::dispatch($clientCommand);
                    Log::debug("WS Job Dispatched");
                } else {
                    $job::dispatch();
                }
                break;
            }
        }
        if ($commandFound) {
            wsEmit("Found");
        }
    }

    public function onClose(Server $server, $fd, $reactorId)
    {
    }

    private function getUser($bearerToken) {
        $tokenguard = new TokenGuard(
            resolve(ResourceServer::class),
            Auth::createUserProvider('users'),
            resolve(TokenRepository::class),
            resolve(ClientRepository::class),
            resolve('encrypter')
        );
        $request = HttpRequest::create('/');
        $request->headers->set('Authorization', 'Bearer ' . $bearerToken);
        return ($tokenguard->user($request));
    }
}
