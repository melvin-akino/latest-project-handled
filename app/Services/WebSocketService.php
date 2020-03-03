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
        $this->wsTable = app('swoole')->wsTable;

        $this->commands = [
            'getUserSport'       => 'App\Jobs\WsUserSport',
            'getSelectedLeagues' => [
                'App\Jobs\WsSelectedLeagues',
                'App\Jobs\WsAdditionalLeagues',
                'App\Jobs\WsForRemovalLeagues',
            ],
            'getWatchlist'       => 'App\Jobs\WsWatchlist',
            'getEvents'          => 'App\Jobs\WsEvents',
            'getSelectedSport'   => 'App\Jobs\WsSelectedSport'
        ];
    }

    public function onOpen(Server $server, Request $request)
    {
        $user = $this->getUser($request->get['token']);
        $userId = $user ? $user['id'] : 0;

        $server->wsTable->set('uid:' . $userId, ['value' => $request->fd]);
        $server->wsTable->set('fd:' . $request->fd, ['value' => $userId]);
    }

    public function onMessage(Server $server, Frame $frame)
    {
        $user = $server->wsTable->get('fd:' . $frame->fd);
        $clientCommand = explode('_', $frame->data);
        foreach ($this->commands as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if ($clientCommand[0] == $key) {
                        $this->dispatchJob($user, $v, $clientCommand);
                    }
                }
            } else if ($clientCommand[0] == $key) {
                $this->dispatchJob($user, $this->commands[$clientCommand[0]], $clientCommand);
                break;
            }
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

    private function dispatchJob($user, $job, $clientCommand)
    {
        if (count($clientCommand) > 1) {
            $job::dispatch($user['value'], $clientCommand);
            Log::debug("WS Job Dispatched");
        } else {
            $job::dispatch($user['value']);
        }
    }
}
