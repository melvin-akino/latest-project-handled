<?php

namespace App\Console\Commands;

use App\User;
use App\Models\{
    Currency,
    Source,
    UserWallet AS Wallet,
    WalletLedger
};
use Illuminate\Console\Command;

class UserWalletDepositCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:deposit {userId} {currencyId} {amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deposit to User Wallet';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sourceId = Source::where('source_name', 'DEPOSIT')->first()->id;
        $args     = [
            'user_id'     => $this->argument('userId'),
            'currency_id' => $this->argument('currencyId'),
            'amount'      => $this->argument('amount'),
        ];

        $this->info('Staring transaction...');
        $this->line('Checking if user exists...');

        $user = User::where('id', $args['user_id']);

        if (!$user->exists()) {
            $this->error('[USER][ERROR-404][USER_NOT_FOUND] User with ID: ' . $args['user_id'] . ' not found!');
            $this->error('Command Terminated.');

            return;
        }

        $this->line('Checking if currency is valid...');

        $currency = Currency::where('id', $args['currency_id']);

        if (!$currency->exists()) {
            $this->error('[CURRENCY][ERROR-404][CURRENCY_NOT_FOUND] Currency with ID: ' . $args['currency_id'] . ' not found!');
            $this->error('Command Terminated.');

            return;
        }

        $this->line('Checking for User Wallet...');

        $userWallet    = Wallet::where('user_id', $args['user_id']);
        $walletId      = "";
        $ledgerBalance = 0;

        if ($userWallet->exists()) {
            if (!$userWallet->where('currency_id', $args['currency_id'])->exists()) {
                $this->error('[USER_WALLET][DEPOSIT][ERROR-400][MULTIPLE_CURRENCY] User ID: ' . $args['user_id'] . ' already has an existing wallet. Unable to create another wallet with different currency.');
                $this->error('Command Terminated.');

                return;
            }

            $this->info('User Wallet found!');
            $this->line('Adding amount (' . $args['amount'] . ') to current balance...');

            $currentBalance = $userWallet->first()->balance;
            $walletId       = $userWallet->update([ 'balance' => $currentBalance + $args['amount'] ]);
            $ledgerBalance  = $currentBalance + $args['amount'];
        } else {
            $this->line('Creating User Wallet...');

            $ledgerBalance = $args['amount'];
            $walletId = Wallet::create(
                [
                    'user_id'     => $args['user_id'],
                    'currency_id' => $args['currency_id'],
                    'balance'     => $args['amount'],
                ]
            )->id;
        }

        $this->line('Updating Wallet Ledger...');

        WalletLedger::create(
            [
                'wallet_id' => $walletId,
                'source_id' => $sourceId,
                'debit'     => $args['amount'],
                'credit'    => 0,
                'balance'   => $ledgerBalance,
            ]
        );

        $this->info('Transaction Successful!');

        return;
    }
}
