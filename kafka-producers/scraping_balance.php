<?php

$conf = new RdKafka\Conf();
$conf->set('group.id', 'multiline');
$conf->set('metadata.broker.list', 'kafka:9092');
$conf->set('auto.offset.reset', 'latest');
$conf->set('enable.auto.commit', 'false');

$rk = new RdKafka\Producer($conf);
$topic = $rk->newTopic("BALANCE");

//testData("", "Union La Calera", "Coquimbo", "hg", 1, 'early', 1234567, 2345678, "2020-03-18T01:00:00.000+04:00")

while(true) {
  try {
    echo '.';
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('test123')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('test234')));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('test345')));
  } catch (\Exception $e) {
    echo '!';
  }
 usleep(1000000);
}


function testData($username)
{
  $permittedChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

  return [
      "request_uid" => str_shuffle($permittedChars),
      "request_ts" => milliseconds(),
      "command" => "balance",
      "sub_command" => "transform",
      "data" => [
          "provider" => "hg",
          "username" => $username,
          "available_balance" => number_format(rand(5000, 10000), 2, '.', ''),
          "currency" => 'CNY',
      ]
  ];
}

function milliseconds()
{
  $mt = explode(' ', microtime());
  return bcadd($mt[1], $mt[0], 8);
}
