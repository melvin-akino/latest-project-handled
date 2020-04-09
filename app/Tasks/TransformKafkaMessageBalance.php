<?php

namespace App\Tasks;

use App\Models\CRM\ProviderAccount;
use App\Jobs\WsMinMax;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;
use Exception;

class TransformKafkaMessageBalance extends Task
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle()
    {
        $swoole = app('swoole');

        try {

            $providerId = $swoole->providersTable->get('providerAlias:' . strtolower($this->message->data->provider))['id'];
            $providerAccount = ProviderAccount::where('username', $this->message->data->username)
                                                ->where('provider_id', $providerId)
                                                ->update(['credits' => $this->message->data->available_balance]);

            Log::info('Balance Transformation - Updated');
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
