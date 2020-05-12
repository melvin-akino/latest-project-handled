<?php

namespace App\Providers;

use RdKafka\{Conf, KafkaConsumer, Producer};
use Illuminate\Support\ServiceProvider;

class KafkaServiceProvider extends ServiceProvider
{
    /**
     * Boot method
     *
     * @return void
     */
    public function boot()
    {
        $conf = new Conf();


        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));

        $conf->set('group.id', 'multiline');
        $conf->set('auto.offset.reset', 'latest');
        $conf->set('enable.auto.commit', 'false');
        $conf->set('offset.store.method', 'broker');

        if (env('KAFKA_DEBUG', false)) {
            $conf->set('log_level', LOG_DEBUG);
            $conf->set('debug', 'all');
        }

        $this->app->bind(Producer::class, function () use ($conf) {
            return new Producer($conf);
        });

        $this->app->bind('KafkaProducer', function () use ($conf) {
            return new Producer($conf);
        });

        $this->app->bind('KafkaConsumer', function () use ($conf) {
            return new KafkaConsumer($conf);
        });
    }
}
