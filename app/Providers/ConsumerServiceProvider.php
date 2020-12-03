<?php

namespace App\Providers;

use App\Handlers\{
    BetTransformationHandler,
    EventsTransformationHandler,
    LeaguesTransformationHandler,
    MaintenanceTransformationHandler,
    MinMaxTransformationHandler,
    OpenOrdersTransformationHandler};
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

        $this->app->singleton('LeaguesTransformationHandler', function () {
            return new LeaguesTransformationHandler();
        });

        $this->app->singleton('OpenOrdersTransformationHandler', function () {
            return new OpenOrdersTransformationHandler();
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
