<?php

namespace App\Services;

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
    }

    public function onOpen(Server $server, Request $request)
    {
        $user = $this->getUser($request->get['token']);
        $userId = $user ? $user['id'] : 0;

        $server->wsTable->set('uid:' . $userId, ['value' => $request->fd]);// Bind map uid to fd
        $server->wsTable->set('fd:' . $request->fd, ['value' => $userId]);// Bind map fd to uid

        $server->push($request->fd, 'Welcome to LaravelS');
    }

    public function onMessage(Server $server, Frame $frame)
    {
        $user = $server->wsTable->get('fd:' . $frame->fd);

        $commands = [
            'getUserSport'  => 'App\Jobs\WsUserSport'
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

    public function onHandshake(Request $request, Response $response)
    {
        $secWebSocketKey = $request->header['sec-websocket-key'];
        $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';

        if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
            $response->end();
            return false;
        }

        echo $request->header['sec-websocket-key'];

        $key = base64_encode(sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        $headers = [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => $key,
            'Sec-WebSocket-Version' => '13',
        ];

        // WebSocket connection to 'ws://127.0.0.1:9502/'
        // failed: Error during WebSocket handshake:
        // Response must not include 'Sec-WebSocket-Protocol' header if not present in request: websocket
        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }

        $response->status(101);
        $response->end();
        app('swoole')->push($request->fd, 'This is a handshake');
        return true;
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
