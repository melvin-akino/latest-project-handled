<?php

namespace App\Handlers;

use App\Facades\SwooleHandler;
use App\Models\{EventMarket, SystemConfiguration};
use Exception;

class MinMaxTransformationHandler
{
    protected $data;

    public function init($data)
    {
        $toLogs = [
            "class"       => "MinMaxTransformationHandler",
            "message"     => "Initiating...",
            "module"      => "HANDLER",
            "status_code" => 102,
        ];
        monitorLog('monitor_handlers', 'info', $toLogs);

        $this->data = $data;

        return $this;
    }

    public function handle()
    {
        $swoole                     = app('swoole');
        $topics                     = $swoole->topicTable;
        $minMaxRequests             = $swoole->minMaxRequestsTable;
        $wsTable                    = $swoole->wsTable;
        $provTable                  = $swoole->providersTable;
        $usersTable                 = $swoole->usersTable;
        $currenciesTable            = $swoole->currenciesTable;
        $exchangeRatesTable         = $swoole->exchangeRatesTable;
        $minmaxMarketTable          = $swoole->minmaxMarketTable;
        $userProviderConfigTable    = $swoole->userProviderConfigTable;
        $minmaxOnqueueRequestsTable = $swoole->minmaxOnqueueRequestsTable;

        try {
            $minmaxMarketTable->set('minmax-market:' . $this->data->data->market_id, [
                'value' => $this->data->data->timestamp
            ]);

            foreach ($minMaxRequests as $key => $row) {
                $data = $this->data->data;

                if ($row['market_id'] == $data->market_id) {
                    $memUID = $row['memUID'];
                    $providerSwtId = "providerAlias:" . $data->provider;
                    if (!empty($this->data->message) && $this->data->message != 'onqueue') {
                        foreach ($topics as $_key => $_row) {
                            if (strpos($_row['topic_name'], 'min-max-' . $data->market_id) === 0) {
                                $userId        = explode(':', $_key)[1];
                                $fd            = $wsTable->get('uid:' . $userId);


                                if ($swoole->isEstablished($fd['value'])) {
                                    $swoole->push($fd['value'], json_encode([
                                        'getMinMax' => [
                                            'market_id'   => $memUID,
                                            'provider_id' => $provTable->get($providerSwtId)['id'],
                                            'message'     => $this->data->message
                                        ]
                                    ]));
                                }
                            }
                        }

                        $minMaxRequests->del($memUID . ":" . strtolower($data->provider));
                    } else if ($this->data->message == 'onqueue') {
                        $doesExist = false;
                        foreach ($minmaxOnqueueRequestsTable as $key => $row) {
                            if (strpos($key, 'min-max-' . $data->market_id) === 0) {
                                $doesExist = true;
                                break;
                            }
                        }

                        if (!$doesExist) {
                            $minmaxOnqueueRequestsTable->set('min-max-' . $data->market_id, ['onqueue' => true]);
                        }

                        continue;
                    } else {
                        $minmaxOnqueueRequestsTable->del('min-max-' . $data->market_id);

                        $userCurrency = [
                            'id'   => 1,
                            'code' => "CNY",
                        ];

                        $providerCurrency = [
                            'id'   => 1,
                            'code' => "CNY",
                        ];

                        foreach ($topics as $_key => $_row) {
                            if (strpos($_row['topic_name'], 'min-max-' . $data->market_id) === 0) {
                                $userId        = explode(':', $_key)[1];

                                $userSwtId = "userId:" . $userId;
                                $doesExist = false;
                                foreach ($usersTable as $k => $v) {
                                    if ($k == $userSwtId) {
                                        $doesExist = true;
                                        break;
                                    }
                                }

                                if ($doesExist) {
                                    $userCurrency['id'] = $usersTable->get($userSwtId)['currency_id'];
                                }

                                $doesExist = false;
                                foreach ($provTable as $k => $v) {
                                    if ($k == $providerSwtId) {
                                        $doesExist = true;
                                        break;
                                    }
                                }

                                if ($doesExist) {
                                    $providerCurrency['id'] = $provTable->get($providerSwtId)['currency_id'];
                                    $punterPercentage       = $provTable->get($providerSwtId)['punter_percentage'];
                                }

                                $userProviderSwtId = implode(':', [
                                    "userId" . $userId,
                                    "pId:" . $provTable->get($providerSwtId)['id'],
                                ]);

                                $doesExist = false;
                                foreach ($userProviderConfigTable as $k => $v) {
                                    if ($k == $userProviderSwtId) {
                                        $doesExist = true;
                                        break;
                                    }
                                }

                                if ($doesExist) {
                                    $punterPercentage = $userProviderConfigTable->get($userProviderSwtId)['punter_percentage'];
                                }

                                $maxBetDisplay = SystemConfiguration::getSystemConfigurationValue('MAX_BET')->value;
                                $maximum       = floor((($data->maximum) * ($punterPercentage / 100)) * 100 ) / 100;
                                $timeDiff      = time() - (int) $data->timestamp;
                                $age           = ($timeDiff > 60) ? floor($timeDiff / 60) . 'm' : $timeDiff . 's';
                                $transformed   = [
                                    "sport_id"    => $data->sport,
                                    "provider_id" => $provTable->get($providerSwtId)['id'],
                                    "provider"    => strtoupper($data->provider),
                                    "min"         => $data->minimum,
                                    "max"         => ($maximum <= $maxBetDisplay) ? $maximum : $maxBetDisplay,
                                    "price"       => (double) $data->odds,
                                    "points"      => $data->points,
                                    'market_id'   => $memUID,
                                    'age'         => $age,
                                    'message'     => ''
                                ];

                                if ($providerCurrency['id'] != $userCurrency['id']) {
                                    foreach ($currenciesTable as $currencyKey => $currencyRow) {
                                        if (strpos($currencyKey, 'currencyId:' . $userCurrency['id']) === 0) {
                                            $userCurrency['code'] = $currenciesTable->get($currencyKey)['code'];
                                        }

                                        if (strpos($currencyKey, 'currencyId:' . $providerCurrency['id']) === 0) {
                                            $providerCurrency['code'] = $currenciesTable->get($currencyKey)['code'];
                                        }
                                    }

                                    $exchangeRate = 1;
                                    $erSwtId      = implode(':', [
                                        "from:" . $providerCurrency['code'],
                                        "to:" . $userCurrency['code'],
                                    ]);

                                    $doesExist = false;
                                    foreach ($exchangeRatesTable as $k => $v) {
                                        if ($k == $erSwtId) {
                                            $doesExist = true;
                                            break;
                                        }
                                    }

                                    if ($doesExist) {
                                        $exchangeRate = $exchangeRatesTable->get($erSwtId)['exchange_rate'];
                                    }

                                    $transformed['min'] = ceil(($data->minimum * $exchangeRate) * 100 ) / 100;

                                    if ($data->minimum == $data->maximum) {
                                        $max = $transformed['min'];
                                    } else {
                                        $max = floor((($data->maximum * $exchangeRate) * ($punterPercentage / 100)) * 100) / 100;
                                    }

                                    $transformed['max'] = ($max <= $maxBetDisplay) ? $max : $maxBetDisplay;
                                }

                                SwooleHandler::setValue('minmaxDataTable', 'minmax-market:' . $data->market_id, [
                                    'min'       => $data->minimum,
                                    'max'       => $data->maximum,
                                    'odds'      => (double) $data->odds,
                                    'points'    => $data->points,
                                    'market_id' => $data->market_id,
                                    'mem_uid'   => $memUID,
                                    "provider"  => strtoupper($data->provider),
                                    'ts'        => getMilliseconds()
                                ]);

                                EventMarket::updateProviderEventMarketsByMemUIDWithOdds($data->market_id, $transformed['price']);

                                $fd            = $wsTable->get('uid:' . $userId);

                                if ($swoole->isEstablished($fd['value'])) {
                                    $minMaxRequests[$memUID . ":" . strtolower($data->provider)]['odds'] = $transformed['price'];

                                    $swoole->push($fd['value'], json_encode([
                                        'getMinMax' => $transformed
                                    ]));
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $toLogs = [
                "class"       => "MinMaxTransformationHandler",
                "message"     => "Line " . $e->getLine() . " | " . $e->getMessage(),
                "module"      => "HANDLER_ERROR",
                "status_code" => $e->getCode(),
            ];
            monitorLog('monitor_handlers', 'error', $toLogs);
        }
    }

    public function finish()
    {
        $toLogs = [
            "class"       => "MinMaxTransformationHandler",
            "message"     => "Transformed",
            "module"      => "HANDLER",
            "status_code" => 200,
        ];
        monitorLog('monitor_handlers', 'info', $toLogs);
    }
}
