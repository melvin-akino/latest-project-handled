<?php

namespace App\Providers;

use App\Handlers\{BalanceTransformationHandler,
    BetTransformationHandler,
    EventsTransformationHandler,
    MaintenanceTransformationHandler,
    MinMaxTransformationHandler,
    OpenOrdersTransformationHandler,
    SettlementTransformationHandler};
use Illuminate\Support\ServiceProvider;

class ConsumerServiceProvider extends ServiceProvider
{
    /**
     * Boot method
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('EventsTransformationHandler', function () {
            return new EventsTransformationHandler();
        });

        $this->app->singleton('BalanceTransformationHandler', function () {
            return new BalanceTransformationHandler();
        });

        $this->app->singleton('OpenOrdersTransformationHandler', function () {
            return new OpenOrdersTransformationHandler();
        });

        $this->app->singleton('SettlementTransformationHandler', function () {
            return new SettlementTransformationHandler();
        });

        $this->app->singleton('MaintenanceTransformationHandler', function () {
            return new MaintenanceTransformationHandler();
        });

        $this->app->singleton('MinMaxTransformationHandler', function () {
            return new MinMaxTransformationHandler();
        });

        $this->app->singleton('BetTransformationHandler', function () {
            return new BetTransformationHandler();
        });
    }
}
