<?php
$conf = new RdKafka\Conf();
$conf->set('group.id', 'multiline');
$conf->set('metadata.broker.list', 'ec2-3-133-143-124.us-east-2.compute.amazonaws.com:9092');
$conf->set('auto.offset.reset', 'latest');
$conf->set('enable.auto.commit', 'false');
$rk = new RdKafka\KafkaConsumer($conf);
$rk->subscribe(['ALEX-SCRAPING-ODDS']);
$msg = $rk->consume(12000);
var_dump($msg);