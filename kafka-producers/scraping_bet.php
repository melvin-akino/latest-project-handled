<?php

$conf = new RdKafka\Conf();
$conf->set("group.id", "multiline");
$conf->set("metadata.broker.list", "kafka:9092");
$conf->set("auto.offset.reset", "latest");
$conf->set("enable.auto.commit", "false");

$rk    = new RdKafka\Producer($conf);
$topic = $rk->newTopic("PLACED-BET");

while (true) {
    try {
        echo ".";

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData()));
    } catch (\Exception $e) {
        echo "!";
    }

    usleep(10000000);
}

function testData()
{
    $permittedChars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

    return [
        "request_uid" => str_shuffle($permittedChars) . "-4",
        "request_ts"  => milliseconds(),
        "command"     => "bet",
        "sub_command" => "transform",
        "data"        => [
            "provider"  => "hg",
            "sport"     => 1,
            "status"    => "success",
            "market_id" => "5ed0d0d2a1f0f",
            "odds"      => "1.45",
            "stake"     => "150.0",
            "to_win"    => "217.5",
            "score"     => "0 - 0",
            "bet_id"    => "OU12595914511",
            "reason"    => "Salamat, Shopee!",
        ],
    ];
}

function milliseconds()
{
    $mt = explode(" ", microtime());

    return bcadd($mt[1], $mt[0], 8);
}