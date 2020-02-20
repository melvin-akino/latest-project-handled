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
        $user = $this->getUser($request->get['token']);
        $userId = $user ? $user['id'] : 0;

        $server->wsTable->set('uid:' . $userId, ['value' => $request->fd]);
        $server->wsTable->set('fd:' . $request->fd, ['value' => $userId]);

        $server->push($request->fd, 'Welcome to LaravelS');
    }

    public function onMessage(Server $server, Frame $frame)
    {
        $user = $server->wsTable->get('fd:' . $frame->fd);

        $commands = [
            'getUserSport'         => 'App\Jobs\WsUserSport',
            'getSelectedLeagues'   => 'App\Jobs\WsSelectedLeagues',
            'getAdditionalLeagues' => 'App\Jobs\WsAdditionalLeagues',
            'getForRemovalLeagues' => 'App\Jobs\WsForRemovalLeagues'
        ];
        $commandFound = false;
        foreach ($commands as $key => $value) {
            $clientCommand = explode('_', $frame->data);
            if ($clientCommand[0] == $key) {
                $commandFound = true;
                $job = $commands[$clientCommand[0]];
                if (count($clientCommand) > 0) {
                    $job::dispatch($user['value'], $clientCommand);
                    Log::debug("WS Job Dispatched");
                } else {
                    $job::dispatch($user['value']);
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

    private function getUser($bearerToken)
    {
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
