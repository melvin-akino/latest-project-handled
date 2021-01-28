<?php

namespace App\Tasks;

use App\Facades\SwooleHandler;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SocketDataPush extends Task
{
    protected $message;
    protected $offset;

    public function init($message, $offset)
    {
        $this->offset   = $offset;
        $this->message  = $message;
        return $this;
    }

    public function handle()
    {
        try {
            // Set the starting time of this function, to keep running stats
            $startTime = microtime(true);

            $swoole = app('swoole');

            $payload = $this->message['data']['payload'];
            $userId  = $this->message['data']['user_id'];
            $retry   = $this->message['data']['retry'];

            if ($retry >= 10) {
                unset($this->offset, $this->message);
                return;
            }

            if ($swoole->wsTable->exists('uid:' . $userId)) {
                $fd = $swoole->wsTable->get('uid:' . $userId);
    
                if ($swoole->isEstablished($fd['value'])) {
                    $swoole->push($fd['value'], json_encode($payload));
                    unset($this->offset, $this->message);
                    return;
                }
            }

            $requestId = (string) Str::uuid();
            $this->message['request_uid'] = $requestId;
            $this->message['request_ts']  = getMilliseconds();
            $this->message['data']['retry'] = $retry + 1;
            
            kafkaPush(env('KAFKA_SOCKET', 'SOCKET-DATA'), $this->message, $requestId);

            // Set the end time of the process, to keep running stats.
            $this->statsArray["time"] = microtime(true) - $startTime;
            // Report the stats to the Swoole Table
            // SwooleStats::addStat($this->statsArray);
        } catch (Exception $e) {
            Log::channel($this->channel)->error(json_encode([
                'line'    => $e->getLine(),
                'message' => $e->getMessage(),
                'file'    => $e->getFile()
            ]));
            Log::error(json_encode([
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'message' => $e->getMessage(),
            ]));
        } finally {
            unset($this->offset, $this->message);
            return;
        }
    }
}
