<?php

namespace App\Jobs;

use App\Models\CRM\ProviderAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class TransformKafkaMessageBalance implements ShouldQueue
{
    use Dispatchable;

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
            ProviderAccount::where('username', $this->message->data->username)
                ->where('provider_id', $providerId)
                ->update(['credits' => $this->message->data->available_balance, 'updated_at' => Carbon::now()]);

            Log::info('Balance Transformation - Updated');
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
