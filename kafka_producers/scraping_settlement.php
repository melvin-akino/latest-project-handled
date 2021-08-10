<?php

$conf = new RdKafka\Conf();
$conf->set("group.id", "ml-db-coro");
$conf->set("metadata.broker.list", "kafka:9092");
$conf->set("auto.offset.reset", "latest");
$conf->set("enable.auto.commit", "false");

$rk = new RdKafka\Producer($conf);
$topic = $rk->newTopic("SCRAPING-SETTLEMENTS");

while (true) {
    try {
        // $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData([
        //     [
        //         "provider"    => "isn",
        //         "sport"       => 1,
        //         "id"          => 1,
        //         "user_name"   => "isnuser001",
        //         "status"      => "WIN",
        //         "odds"        => "1.02",
        //         "score"       => "3 - 1",
        //         "stake"       => "105.0",
        //         "profit_loss" => "129.15",
        //         "bet_id"      => "OU1259591469",
        //         "reason"      => ""
        //     ],
        // ])));

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(testData([
            ["provider" => "pin", "sport" => "1", "id" => 1, "username" => "HCP00ML001", "status" => "WIN", "odds" => "1.06", "score" => "0 - 1", "stake" => "324.19", "profit_loss" => "343.64", "bet_id" => "759629245", "reason" => ""],
            ["provider" => "pin", "sport" => "1", "id" => 2, "username" => "HCP00ML002", "status" => "LOSE", "odds" => "1.06", "score" => "0 - 1", "stake" => "324.19", "profit_loss" => "0", "bet_id" => "759629247", "reason" => ""],
            ["provider" => "pin", "sport" => "1", "id" => 3, "username" => "HCP00ML001", "status" => "WIN", "odds" => "1.06", "score" => "0 - 1", "stake" => "324.19", "profit_loss" => "162.10", "bet_id" => "759629249", "reason" => ""],
            ["provider" => "pin", "sport" => "1", "id" => 4, "username" => "HCP00ML002", "status" => "LOSE", "odds" => "1.06", "score" => "0 - 1", "stake" => "324.19", "profit_loss" => "162.10", "bet_id" => "759629251", "reason" => ""],
            ["provider" => "pin", "sport" => "1", "id" => 5, "username" => "HCP00ML002", "status" => "PUSH", "odds" => "1.06", "score" => "0 - 1", "stake" => "324.19", "profit_loss" => "", "bet_id" => "7596292453", "reason" => ""],
            ["provider" => "pin", "sport" => "1", "id" => 6, "username" => "HCP00ML001", "status" => "VOID", "odds" => "1.06", "score" => "0 - 1", "stake" => "324.19", "profit_loss" => "", "bet_id" => "7596292455", "reason" => ""],
            ["provider" => "pin", "sport" => "1", "id" => 7, "username" => "HCP00ML002", "status" => "DRAW", "odds" => "1.06", "score" => "0 - 1", "stake" => "324.19", "profit_loss" => "", "bet_id" => "7596292457", "reason" => ""],
            ["provider" => "pin", "sport" => "1", "id" => 8, "username" => "PWX2306000", "status" => "CANCELLED", "odds" => "1.06", "score" => "0 - 1", "stake" => "324.19", "profit_loss" => "", "bet_id" => "7596292459", "reason" => ""],
            ["provider" => "pin", "sport" => "1", "id" => 9, "username" => "HCP00ML001", "status" => "REJECTED", "odds" => "1.06", "score" => "0 - 1", "stake" => "324.19", "profit_loss" => "", "bet_id" => "7596292461", "reason" => ""],
            ["provider" => "pin", "sport" => "1", "id" => 10, "username" => "HCP00ML004", "status" => "ABNORMAL BET", "odds" => "1.06", "score" => "0 - 1", "stake" => "324.19", "profit_loss" => "", "bet_id" => "7596292463", "reason" => ""],
            ["provider" => "pin", "sport" => "1", "id" => 11, "username" => "HCP00ML002", "status" => "REFUNDED", "odds" => "1.06", "score" => "0 - 1", "stake" => "324.19", "profit_loss" => "", "bet_id" => "7596292465", "reason" => ""],
            ["provider" => "pin", "sport" => "1", "id" => 12, "username" => "PWX2306000", "status" => "WIN", "odds" => "1.06", "score" => "0 - 1", "stake" => "324.19", "profit_loss" => "343.64", "bet_id" => "7596292467", "reason" => ""]
        ])));
        
        for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
            $result = $rk->flush(10000);
            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                break;
            }
        }
        echo '.';
    } catch (\Exception $e) {
        echo '!';
    }

    sleep(60);
}

function testData($data)
{
    $permittedChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    return [
        "request_uid" => str_shuffle($permittedChars),
        "request_ts"  => milliseconds(),
        'command'     => 'settlement',
        'sub_command' => 'transform',
        'data'        => $data
    ];
}

function milliseconds()
{
    $mt = explode(' ', microtime());

    return bcadd($mt[1], $mt[0], 8);
}