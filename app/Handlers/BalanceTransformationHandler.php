<?php

namespace App\Handlers;

use App\Models\CRM\ProviderAccount;
use Illuminate\Support\Facades\Log;
use Exception;

class BalanceTransformationHandler
{
    protected $message;

    public function init($message)
    {
        $this->message = $message;
        return $this;
    }

    public function handle()
    {
        $swoole = app('swoole');

        try {

            $providerId = $swoole->providersTable->get('providerAlias:' . strtolower($this->message->data->provider))['id'];
            ProviderAccount::where('username', $this->message->data->username)
                ->where('provider_id', $providerId)
                ->update(['credits' => $this->message->data->available_balance]);

            Log::info('Balance Transformation - Updated');
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
