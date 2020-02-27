<?php

namespace App\Providers;

use RdKafka\Conf;
use RdKafka\Producer;
use Illuminate\Support\ServiceProvider;

class ProducerServiceProvider extends ServiceProvider
{
    /**
     * Boot method
     *
     * @return void
     */
    public function boot()
    {
        $conf = new Conf();

        $conf->set('group.id', 'multiline');

        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));

        $conf->set('auto.offset.reset', 'smallest');

        $conf->set('enable.auto.commit', 'false');

        if (env('KAFKA_DEBUG', false)) {
            $conf->set('log_level', LOG_DEBUG);
            $conf->set('debug', 'all');
        }

        $this->app->bind(Producer::class, function () use ($conf) {
            return new Producer($conf);
        });
    }
}