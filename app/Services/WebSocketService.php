<?php

namespace App\Services;

use App\Facades\SwooleHandler;
use App\Models\{Provider, SystemConfiguration};
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
            'getSelectedSport'   => 'App\Jobs\WsSelectedSport',
            'getMinMax'          => 'App\Jobs\WsMinMax',
            'getOrder'           => 'App\Jobs\WsOrder',
            'removeMinMax'       => 'App\Jobs\WsRemoveMinMax',
        ];
    }

    public function onOpen(Server $server, Request $request)
    {
        $user   = $this->getUser($request->get['token']);
        $userId = $user ? $user['id'] : 0;

        $server->wsTable->set('uid:' . $userId, ['value' => $request->fd]);
        $server->wsTable->set('fd:' . $request->fd, ['value' => $userId]);

        $providers = Provider::getActiveProviders();
        $providers = $providers->get()->toArray();
        array_map(function($value) use ($server, $request) {
            $maintenanceConfiguration = SystemConfiguration::getSystemConfigurationValue(strtoupper($value['alias']) . '_MAINTENANCE', 'ProviderMaintenance');
            $isMaintenance = false;
            if ($maintenanceConfiguration) {
                $isMaintenance = $maintenanceConfiguration['value'];
            }
            $server->push($request->fd, json_encode([
                'getMaintenance' => [
                    'provider'          => strtolower($value['alias']),
                    'under_maintenance' => $isMaintenance == '1' ? true : false
                ]
            ]));
        }, $providers);
    }

    public function onMessage(Server $server, Frame $frame)
    {
        $user          = $server->wsTable->get('fd:' . $frame->fd);
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
        $user                         = SwooleHandler::getValue('wsTable', 'fd:' . $fd);
        $userId                       = $user['value'];
        $userMinmaxSubscriptions      = [];
        $otherUserMinmaxSubscriptions = [];

        foreach ($server->topicTable as $key => $topic) {
            if ($topic['user_id'] == $userId) {
                if (strpos($topic['topic_name'], 'min-max-') === 0) {// Fetch user's minmax subscriptions
                    $userMinmaxSubscriptions[] = substr($topic['topic_name'], strlen('min-max-'));
                }

                $server->topicTable->del($key);
            } else if (strpos($topic['topic_name'], 'min-max-') === 0) {// Fetch other user's minmax subscriptions
                $otherUserMinmaxSubscriptions[] = substr($topic['topic_name'], strlen('min-max-'));
            }
        }

        $forRemovalOfMinmaxSubscriptions = array_diff($userMinmaxSubscriptions, $otherUserMinmaxSubscriptions);
        foreach ($server->minMaxRequestsTable as $key => $ws) {
            SwooleHandler::decCtr('minMaxRequestsTable', $key);
            if (in_array($ws['market_id'], $forRemovalOfMinmaxSubscriptions)) {
                // SwooleHandler::remove('minmaxDataTable', $ws['memUID']);
                SwooleHandler::remove('minmaxDataTable', $ws['market_id']);
            }
        }

        foreach ($server->minmaxMarketTable as $key => $ws) {
            $marketId = substr($ws['value'], strlen('minmax-market:'));
            if (in_array($marketId, $forRemovalOfMinmaxSubscriptions)) {
                SwooleHandler::remove('minmaxMarketTable', $key);
            }
        }

        foreach ($server->minmaxPayloadTable as $key => $ws) {
            $marketId = substr($ws['value'], strlen('minmax-payload:'));
            if (in_array($marketId, $forRemovalOfMinmaxSubscriptions)) {
                SwooleHandler::remove('minmaxPayloadTable', $key);
            }
        }

        $toLogs = [
            "class"       => "WebSocketService",
            "message"     => "WebSocket Closed",
            "module"      => "WS",
            "status_code" => 200,
        ];
        monitorLog('monitor_ws', 'info', $toLogs);

        SwooleHandler::remove('wsTable', 'fd:' . $fd);
        SwooleHandler::remove('wsTable', 'uid:' . $userId);
    }

    private function getUser($bearerToken)
    {
        try {
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
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "WebSocketService",
                "message"     => "Bearer Token is expired/invalid",
                "module"      => "WS_ERROR",
                "status_code" => 400,
            ];
            monitorLog('monitor_ws', 'error', $toLogs);

            return 0;
        }

    }

    private function dispatchJob($user, $job, $clientCommand)
    {
        if (count($clientCommand) > 1) {
            $job::dispatch($user['value'], $clientCommand);
            $toLogs = [
                "class"       => "WebSocketService",
                "message"     => "WS Job Dispatched",
                "module"      => "WS",
                "status_code" => 200,
            ];
            monitorLog('monitor_ws', 'debug', $toLogs);
        } else {
            $job::dispatch($user['value']);
        }
    }
}
