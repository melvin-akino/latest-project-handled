<?php

$conf = new RdKafka\Conf();
$conf->set("group.id", "ml");
$conf->set("metadata.broker.list", "kafka:9092");
$conf->set("auto.offset.reset", "latest");
$conf->set("enable.auto.commit", "false");

$rk    = new RdKafka\Producer($conf);
$topic = $rk->newTopic("PLACED-BET");

while (true) {
    try {
        echo ".";

        $no = 81;

        // $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData('isn', 'HDPA3111111', 1.23, 150, 'OU12595914' . $no, $no)));

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode([
            'request_uid' => '5a0647f4-62ce-4cb8-9be4-55453c378cc1-2483',
            'request_ts'  => milliseconds(),
            'command'     => 'bet',
            'sub_command' => 'transform',
            'data'        => [
                'provider'  => 'hg',
                'sport'     => 1,
                'status'    => 'failed',
                'market_id' => 'REH4920433',
                'odds'      => '0.94',
                'stake'     => '50',
                'to_win'    => '',
                'score'     => '2 - 0',
                'bet_id'    => '',
                'reason'    => 'Bet maintenance in progress. Please try again later.',
            ],
            'response_message' => 'success: data from provider',
        ]);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode([
            'request_uid' => '5a0647f4-62ce-4cb8-9be4-55453c378cc1-2482',
            'request_ts'  => milliseconds(),
            'command'     => 'bet',
            'sub_command' => 'transform',
            'data'        => [
                'provider'  => 'hg',
                'sport'     => 1,
                'status'    => 'failed',
                'market_id' => 'HREC4920434',
                'odds'      => '0.51',
                'stake'     => '50',
                'to_win'    => '',
                'score'     => '2 - 0',
                'bet_id'    => '',
                'reason'    => 'Bet maintenance in progress. Please try again later.',
            ],
            'response_message' => 'success: data from provider',
        ]);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode([
            'request_uid' => '5a0647f4-62ce-4cb8-9be4-55453c378cc1-2474',
            'request_ts'  => milliseconds(),
            'command'     => 'bet',
            'sub_command' => 'transform',
            'data'        => [
                'provider'  => 'hg',
                'sport'     => 1,
                'status'    => 'failed',
                'market_id' => 'HROUC4925628',
                'odds'      => '0.37',
                'stake'     => '50',
                'to_win'    => '',
                'score'     => '0 - 0',
                'bet_id'    => '',
                'reason'    => 'Bet maintenance in progress. Please try again later.',
            ],
            'response_message' => 'success: data from provider',
        ]);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode([
            'request_uid' => '5a0647f4-62ce-4cb8-9be4-55453c378cc1-2472',
            'request_ts'  => milliseconds(),
            'command'     => 'bet',
            'sub_command' => 'transform',
            'data'        => [
                'provider'  => 'hg',
                'sport'     => 1,
                'status'    => 'failed',
                'market_id' => 'HREC4925628',
                'odds'      => '0.82',
                'stake'     => '50',
                'to_win'    => '',
                'score'     => '0 - 0',
                'bet_id'    => '',
                'reason'    => 'Bet maintenance in progress. Please try again later.',
            ],
            'response_message' => 'success: data from provider',
        ]);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode([
            'request_uid' => '5a0647f4-62ce-4cb8-9be4-55453c378cc1-2471',
            'request_ts'  => milliseconds(),
            'command'     => 'bet',
            'sub_command' => 'transform',
            'data'        => [
                'provider'  => 'hg',
                'sport'     => 1,
                'status'    => 'failed',
                'market_id' => 'HRMN4925628',
                'odds'      => '1.31',
                'stake'     => '50',
                'to_win'    => '',
                'score'     => '0 - 0',
                'bet_id'    => '',
                'reason'    => 'Bet maintenance in progress. Please try again later.',
            ],
            'response_message' => 'success: data from provider',
        ]);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode([
            'request_uid' => '5a0647f4-62ce-4cb8-9be4-55453c378cc1-2470',
            'request_ts'  => milliseconds(),
            'command'     => 'bet',
            'sub_command' => 'transform',
            'data'        => [
                'provider'  => 'hg',
                'sport'     => 1,
                'status'    => 'failed',
                'market_id' => 'REVEN4925627',
                'odds'      => '1.88',
                'stake'     => '20',
                'to_win'    => '',
                'score'     => '0 - 0',
                'bet_id'    => '',
                'reason'    => 'Bet maintenance in progress. Please try again later.',
            ],
            'response_message' => 'success: data from provider',
        ]);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode([
            'request_uid' => '5a0647f4-62ce-4cb8-9be4-55453c378cc1-2469',
            'request_ts'  => milliseconds(),
            'command'     => 'bet',
            'sub_command' => 'transform',
            'data'        => [
                'provider'  => 'hg',
                'sport'     => 1,
                'status'    => 'failed',
                'market_id' => 'ROUH4920421',
                'odds'      => '0.76',
                'stake'     => '50',
                'to_win'    => '',
                'score'     => '1 - 0',
                'bet_id'    => '',
                'reason'    => 'Bet maintenance in progress. Please try again later.',
            ],
            'response_message' => 'success: data from provider',
        ]);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode([
            'request_uid' => '5a0647f4-62ce-4cb8-9be4-55453c378cc1-2468',
            'request_ts'  => milliseconds(),
            'command'     => 'bet',
            'sub_command' => 'transform',
            'data'        => [
                'provider'  => 'hg',
                'sport'     => 1,
                'status'    => 'failed',
                'market_id' => 'RMC4920427',
                'odds'      => '1.22',
                'stake'     => '50',
                'to_win'    => '',
                'score'     => '0 - 1',
                'bet_id'    => '',
                'reason'    => 'Bet maintenance in progress. Please try again later.',
            ],
            'response_message' => 'success: data from provider',
        ]);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode([
            'request_uid' => '5a0647f4-62ce-4cb8-9be4-55453c378cc1-2467',
            'request_ts'  => milliseconds(),
            'command'     => 'bet',
            'sub_command' => 'transform',
            'data'        => [
                'provider'  => 'hg',
                'sport'     => 1,
                'status'    => 'failed',
                'market_id' => 'ROUC4920421',
                'odds'      => '0.81',
                'stake'     => '50',
                'to_win'    => '',
                'score'     => '1 - 0',
                'bet_id'    => '',
                'reason'    => 'Bet maintenance in progress. Please try again later.',
            ],
            'response_message' => 'success: data from provider',
        ]);
    } catch (\Exception $e) {
        echo "!";
    }

    sleep(50);
}

function testData($provider, $marketId, $odds, $stake, $betId, $no)
{
    $permittedChars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

    return [
        "request_uid" => str_shuffle($permittedChars) . "-" . $no,
        "request_ts"  => milliseconds(),
        "command"     => "bet",
        "sub_command" => "transform",
        "data"        => [
            "provider"  => $provider,
            "sport"     => 1,
            "status"    => "success",
            "market_id" => $marketId,
            "odds"      => $odds,
            "stake"     => $stake,
            "to_win"    => "129.15",
            "score"     => "0 - 0",
            "bet_id"    => $betId,
            "reason"    => "",
        ],
    ];
}

function milliseconds()
{
    $mt = explode(" ", microtime());

    return bcadd($mt[1], $mt[0], 8);
}
