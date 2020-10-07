<?php

namespace App\Providers;

use App\Handlers\ProducerHandler;
use RdKafka\{Conf, Consumer, KafkaConsumer, Producer, TopicConf};
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
        $conf = $alwaysLatestConf = $lowConf = new Conf();


        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));
        $conf->set('group.id', 'ml');
        $conf->set('auto.offset.reset', 'latest');
        $conf->set('enable.auto.commit', 'false');
        if (!in_array(env('APP_ENV'), ["local", "testing"])) {
            $conf->set('max.poll.interval.ms', 10000000);
        }

        $alwaysLatestConf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));
        $alwaysLatestConf->set('group.id', 'ml');
        $alwaysLatestConf->set('auto.offset.reset', 'latest');
        $alwaysLatestConf->set('enable.auto.commit', 'false');
        if (!in_array(env('APP_ENV'), ["local", "testing"])) {
            $alwaysLatestConf->set('max.poll.interval.ms', 10000000);
        }

        $lowConf->set('group.id', 'ml');

        if (env('KAFKA_DEBUG', false)) {
            $conf->set('log_level', LOG_DEBUG);
            $conf->set('debug', 'all');
        }

        $this->app->bind(Producer::class, function () use ($conf) {
            return new Producer($conf);
        });

        $this->app->singleton('KafkaProducer', function () use ($conf) {
            return new Producer($conf);
        });

        $this->app->bind('KafkaConsumer', function () use ($conf) {
            return new KafkaConsumer($conf);
        });

        $this->app->bind('KafkaLatestConsumer', function () use ($alwaysLatestConf) {
            return new KafkaConsumer($alwaysLatestConf);

        });

        $this->app->bind('LowLevelConsumer', function () use ($lowConf) {
            $lowLevelConsumer = new Consumer($lowConf);
            $lowLevelConsumer->addBrokers(env('KAFKA_BROKERS', 'kafka:9092'));
            return $lowLevelConsumer;
        });

        $this->app->singleton('ProducerHandler', function () use ($conf) {
            return new ProducerHandler(new Producer($conf));
        });

        $this->app->bind('KafkaTopicConf', function () {
            $topicConf = new TopicConf();
            $topicConf->set('enable.auto.commit', 'false');
            $topicConf->set('auto.commit.interval.ms', 100);
            $topicConf->set('offset.store.method', 'broker');
            $topicConf->set('auto.offset.reset', 'latest');

            return $topicConf;
        });
    }
}
