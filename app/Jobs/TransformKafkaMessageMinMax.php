<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Exception;

class TransformKafkaMessageMinMax implements ShouldQueue
{
    use Dispatchable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
        Log::info('Task: MinMax construct');
    }

    public function handle()
    {
        Log::info('Task: MinMax handle');
        $swoole = app('swoole');

        $topics             = $swoole->topicTable;
        $minMaxRequests     = $swoole->minMaxRequestsTable;
        $wsTable            = $swoole->wsTable;
        $provTable          = $swoole->providersTable;
        $usersTable         = $swoole->usersTable;
        $currenciesTable    = $swoole->currenciesTable;
        $exchangeRatesTable = $swoole->exchangeRatesTable;
        $minmaxMarketTable  = $swoole->minmaxMarketTable;
        $minMaxCachesTable  = $swoole->minMaxCachesTable;

        try {
            $minmaxMarketTable->set('minmax-market:' . $this->data->data->market_id, [
                'value' => $this->data->data->timestamp
            ]);
            foreach ($minMaxRequests AS $key => $row) {
                $data = $this->data->data;
                if ($row['market_id'] == $data->market_id) {
                    $memUID = $row['memUID'];
                    foreach ($topics AS $_key => $_row) {
                        if (strpos($_row['topic_name'], 'min-max-' . $memUID) === 0) {
                            $userId = explode(':', $_key)[1];
                            $fd     = $wsTable->get('uid:' . $userId);

                            if (!empty($this->data->message) && $this->data->message != 'onqueue') {
                                $swoole->push($fd['value'], json_encode([
                                    'getMinMax' => ['message' => $this->data->message]
                                ]));

                                $minMaxRequests->del('memUID:' . $memUID);

                                Log::info("MIN MAX Transformation - Message Found");
                            } else if ($this->data->message == 'onqueue') {
                                continue;
                            } else {
                                /** AS DEFAULT */
                                $providerCurrency = [
                                    'id'   => 1,
                                    'code' => "CNY",
                                ];
                                $providerSwtId    = "providerAlias:" . $data->provider;

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

                                $userCurrency = [
                                    'id'   => 1,
                                    'code' => "CNY",
                                ];
                                $userSwtId    = "userId:" . $userId;

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

                                $maximum = (double) $data->maximum * ($punterPercentage / 100);

                                $timeDiff = time() - (int)$data->timestamp;
                                $age      = ($timeDiff > 60) ? floor($timeDiff / 60) . 'm' : $timeDiff . 's';

                                $transformed = [
                                    "sport_id"    => $data->sport,
                                    "provider_id" => $provTable->get($providerSwtId)['id'],
                                    "provider"    => strtoupper($data->provider),
                                    "min"         => $data->minimum,
                                    "max"         => $maximum,
                                    "price"       => $data->odds,
                                    "priority"    => $provTable->get($providerSwtId)['priority'],
                                    'market_id'   => $memUID,
                                    'age'         => $age,
                                    'message'     => ''
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
                                        "to:" . $providerCurrency['code'],
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

                                    $transformed['min'] = $data->minimum / $exchangeRate;
                                    $transformed['max'] = $data->maximum / $exchangeRate;
                                }

                                Log::info('Task: MinMax emitWS');

                                $minMaxCachesTable->set('memUID:' . $memUID, ['value' => json_encode($transformed)]);

                                $swoole->push($fd['value'], json_encode([
                                    'getMinMax' => $transformed
                                ]));

                                Log::info("MIN MAX Transformation - Transformed");
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function finish()
    {
        Log::info("Task: MinMax Done");
    }
}
