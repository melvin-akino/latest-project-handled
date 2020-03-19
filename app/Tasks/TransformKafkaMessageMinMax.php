<?php

namespace App\Tasks;

use Hhxsv5\LaravelS\Swoole\Task\Task;

class TransformKafkaMessageMinMax extends Task
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        $swoole = app('swoole');

        $topics             = $swoole->topicTable;
        $minMaxRequests     = $swoole->minMaxRequestsTable;
        $wsTable            = $swoole->wsTable;
        $provTable          = $swoole->providersTable;
        $usersTable         = $swoole->usersTable;
        $currenciesTable    = $swoole->currenciesTable;
        $exchangeRatesTable = $swoole->exchangeRatesTable;

        $transformed = [];
        $fd          = "";

        foreach ($minMaxRequests AS $key => $row) {
            foreach ($this->data->data AS $_data) {
                if ($row['market_id'] == $_data->market_id) {
                    $memUID = substr($key, strlen('memUID:'));

                    foreach ($topics AS $_key => $_row) {
                        if (strpos($_row['topic_name'], 'min-max-' . $memUID) === 0) {
                            $userId = explode(':', $_key)[1];
                            $fd     = $wsTable->get('uid:' . $userId);

                            /** AS DEFAULT */
                            $providerCurrency = [
                                'id'   => 1,
                                'code' => "CNY",
                            ];
                            $providerSwtId    = "providerAlias:" . $_data->provider;

                            if ($provTable->exists($providerSwtId)) {
                                $providerCurrency['id'] = $provTable->get($providerSwtId)['currency_id'];
                            }

                            $userCurrency = [
                                'id'   => 1,
                                'code' => "CNY",
                            ];
                            $userSwtId    = "userId:" . $userId;

                            if ($usersTable->exists($userSwtId)) {
                                $userCurrency['id'] = $usersTable->get($userSwtId)['currency_id'];
                            }

                            $transformed[] = [
                                "sport_id"    => $_data->sport_id,
                                "provider_id" => $provTable->get($providerSwtId)['id'],
                                "provider"    => strtoupper($_data->provider),
                                "min"         => $_data->minimum,
                                "max"         => $_data->maximum,
                                "price"       => $_data->odds,
                                "priority"    => $provTable->get($providerSwtId)['priority'],
                            ];

                            if (!$providerCurrency['id'] == $userCurrency['id']) {
                                foreach ($currenciesTable AS $currencyKey => $currencyRow) {
                                    if (strpos($currencyKey, 'currencyId:' . $userCurrency['id']) === 0) {
                                        $userCurrency['code'] = $currenciesTable->get($currencyKey)['code'];
                                    }

                                    if (strpos($currencyKey, 'currencyId:' . $providerCurrency['id']) === 0) {
                                        $providerCurrency['code'] = $currenciesTable->get($currencyKey)['code'];
                                    }
                                }

                                $erSwtId = implode(':', [
                                    "from:" . $userCurrency['code'],
                                    "to:"   . $providerCurrency['code'],
                                ]);

                                if ($exchangeRatesTable->exists($erSwtId)) {
                                    $exchangeRate = $exchangeRatesTable->get($erSwtId)['exchange_rate'];
                                }

                                $transformed['min'] = $_data->minimum / $exchangeRate;
                                $transformed['max'] = $_data->maximum / $exchangeRate;
                            }
                        }
                    }
                }
            }
        }

        $swoole->push($fd['value'], json_encode([
            'getMinMax' => $transformed
        ]));
    }

    private static function sortArrayByKey($array, $order, $key)
    {
        $column = array_column($array, $key);

        if ($order == "desc") {
            return array_multisort($column, SORT_DESC, $array);
        }

        return array_multisort($column, SORT_ASC, $array);
    }
}
