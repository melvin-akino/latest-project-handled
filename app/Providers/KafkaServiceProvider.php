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
        $conf = $alwaysLatestConf = new Conf();


        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));
        $conf->set('group.id', 'ml');
        $conf->set('auto.offset.reset', 'latest');
        $conf->set('enable.auto.commit', 'false');
        if (env('APP_ENV') != "local") {
            $conf->set('max.poll.interval.ms', 100000);
        }

        $alwaysLatestConf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));
        $alwaysLatestConf->set('group.id', 'ml');
        $alwaysLatestConf->set('auto.offset.reset', 'latest');
        $alwaysLatestConf->set('enable.auto.commit', 'false');
        if (env('APP_ENV') != "local") {
            $alwaysLatestConf->set('max.poll.interval.ms', 100000);
        }

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

        $this->app->bind('KafkaLatestConsumer', function () use ($alwaysLatestConf) {
            return new KafkaConsumer($alwaysLatestConf);
        });
    }
}
