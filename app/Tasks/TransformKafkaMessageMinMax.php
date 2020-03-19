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
        /**
            {
                "request_uid": "77f61545-6756-4463-97a1-b7d8b3824cd9",
                "request_ts": "123456789",
                "command": "minmax",
                "sub_command": "transform",
                "data": [
                    {
                        "provider"  : "hg",
                        "sport"     : 1,
                        "market_id" : "EOE4044819",
                        "odds"      : "1.09",
                        "minimum"   : "50.00",
                        "maximum"   : "100000.00"
                    }
                ]
            }

            'providers' => [ // key format [providerAlias:strtolower($providerAlias)] => [id = $id, alias = $alias]
                'size'   => 500,
                'column' => [
                    ['name' => 'id',          'type' => \Swoole\Table::TYPE_INT ],
                    ['name' => 'alias',       'type' => \Swoole\Table::TYPE_STRING, 'size' => 10 ],
                    ['name' => 'priority',    'type' => \Swoole\Table::TYPE_INT ],
                    ['name' => 'is_enabled',  'type' => \Swoole\Table::TYPE_INT ],
                    ['name' => 'currency_id', 'type' => \Swoole\Table::TYPE_INT ],
                ],
            ],

            'exchangeRates' => [
                'size' => 51200,
                'column' => [ // KEY FORMAT: [from:$from_currency_code:to:$to_currency_code]
                    [ 'name' => 'default_amount', 'type' => \Swoole\Table::TYPE_FLOAT ],
                    [ 'name' => 'exchange_rate',  'type' => \Swoole\Table::TYPE_FLOAT ],
                ],
            ],

            'minmax' => [
                'size' => 102400,
                'column' => [ // KEY FORMAT: [userId:$userId:memUID:$memUID]
                    [ 'name' => 'id',             'type' => \Swoole\Table::TYPE_INT ],
                    [ 'name' => 'user_id',        'type' => \Swoole\Table::TYPE_INT ],
                    [ 'name' => 'sport_id',       'type' => \Swoole\Table::TYPE_INT ],
                    [ 'name' => 'provider_alias', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 10 ],
                    [ 'name' => 'bet_identifier', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 50 ],
                    [ 'name' => 'order_id',       'type' => \Swoole\Table::TYPE_STRING, 'size' => 50 ],
                    [ 'name' => 'min',            'type' => \Swoole\Table::TYPE_FLOAT ],
                    [ 'name' => 'max',            'type' => \Swoole\Table::TYPE_FLOAT ],
                ],
            ],

            minMaxData: [
                {
                    provider_id: 1,
                    provider: 'HG',
                    min: 100,
                    max: 500,
                    price: 0.69,
                    priority: 1
                },
                {
                    provider_id: 2,
                    provider: 'PIN',
                    min: 350,
                    max: 1000,
                    price: this.odd_details.odds,
                    priority: 2
                },
                {
                    provider_id: 3,
                    provider: 'ISN',
                    min: 600,
                    max: 2000,
                    price: this.odd_details.odds,
                    priority: 3
                }
            ]
        **/

        $swoole = app('swoole');

        $topics             = $swoole->topicTable;
        $minMaxRequests     = $swoole->minMaxRequestsTable;
        $wsTable            = $swoole->wsTable;
        $provTable          = $swoole->providersTable;
        $usersTable         = $swoole->usersTable;
        $currenciesTable    = $swoole->currenciesTable;
        $exchangeRatesTable = $swoole->exchangeRatesTable;

        $transformed = [];
        $userId      = "";

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
