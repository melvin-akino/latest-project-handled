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
        $conf = $lowConf = new Conf();

        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));

        $this->app->bind(Producer::class, function () use ($conf) {
            return new Producer($conf);
        });

        $this->app->singleton('KafkaProducer', function () use ($conf) {
            return new Producer($conf);
        });

        $this->app->bind('KafkaConsumer', function () use ($conf) {
            $conf->set('group.id', env('KAFKA_GROUP_ID', 'ml'));
            $conf->set('auto.offset.reset', 'latest');
            $conf->set('enable.auto.commit', 'false');
            if (!in_array(env('APP_ENV'), ["local", "testing"])) {
                $conf->set('max.poll.interval.ms', 10000000);
            }
            
            return new KafkaConsumer($conf);
        });

        $this->app->bind('LowLevelConsumer', function () use ($lowConf) {
            $lowConf->set('group.id', env('KAFKA_GROUP_ID', 'ml'));

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
