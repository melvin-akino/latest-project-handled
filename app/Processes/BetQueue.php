<?php

namespace App\Processes;

use App\Jobs\KafkaPush;
use App\Models\{Order, OrderLogs, SystemConfiguration};
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Swoole\Http\Server;
use Swoole\Process;
use Exception;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class BetQueue implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static $quit = false;

    public static function callback(Server $swoole, Process $process)
    {
        try {
            if ($swoole->data2SwtTable->exist('data2Swt')) {
                while (!self::$quit) {
                    $betQueue = Redis::hgetall('queue');

                    $maxRetryCount = SystemConfiguration::getSystemConfigurationValue('RETRY_COUNT');
                    $retryExpiry = SystemConfiguration::getSystemConfigurationValue('RETRY_EXPIRY');

                    if (!empty($redis)) {
                        foreach ($betQueue as $key => $bet) {
                            if ($bet['retry_count'] < $maxRetryCount->value) {
                                $now = Carbon::now();
                                if ($now->diffInSeconds($bet['created_at']) <= $retryExpiry) {
                                    $selectedAccount = null; // @TODO call bet selection

                                    DB::beginTransaction();
                                    Order::where('id', $bet['id'])->update(
                                        [
                                            'status' => 'PENDING'
                                        ]
                                    );

                                    OrderLogs::create(
                                        [
                                            'user_id'             => $bet['id'],
                                            'provider_id'         => $bet['provider_id'],
                                            'sport_id'            => $bet['sport_id'],
                                            'bet_id'              => $bet['bet_id'],
                                            'bet_selection'       => $bet['bet_selection'],
                                            'status'              => 'PENDING',
                                            'settled_date'        => null,
                                            'reason'              => '',
                                            'profit_loss'         => 0,
                                            'order_id'            => $bet['id'],
                                            'provider_account_id' => $selectedAccount
                                        ]
                                    );

                                    $requestId = Str::uuid() . "-" . $bet['id'];
                                    $requestTs = getMilliseconds();
                                    $payload = [
                                        'request_uid' => $requestId,
                                        'request_ts'  => $requestTs,
                                        'sub_command' => 'place',
                                        'command'     => 'bet'
                                    ];

                                    $payload['data'] = [
                                        'provider'  => $bet['alias'],
                                        'sport'     => $bet['sport_id'],
                                        'stake'     => $bet['actual_stake'],
                                        'odds'      => $bet['odds'],
                                        'market_id' => $bet['market_id'],
                                        'event_id'  => $bet['event_id'],
                                        'score'     => $bet['score'],
                                        'username'  => $bet['username']
                                    ];

                                    KafkaPush::dispatch(
                                        $bet['alias'] . env('KAFKA_SCRAPE_ORDER_REQUEST_POSTFIX', '_bet_req'),
                                        $payload,
                                        $requestId
                                    );

                                    Redis::hDel('queue', $key);
                                } else {
                                    // Expired retry
                                    Redis::hDel('queue', $key);
                                    continue;
                                }
                            } else {
                                // Max count retry
                                Redis::hdel('queue', $key);
                                continue;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "BetQueue",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "PRODUCE_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_process', 'error', $toLogs);
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }
}
