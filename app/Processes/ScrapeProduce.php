<?php

namespace App\Processes;

use App\Handlers\ProducerHandler;
use App\Jobs\KafkaPush;
use Illuminate\Support\Facades\DB;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Str;
use Swoole\Http\Server;
use Swoole\Process;
use PrometheusMatric;
use App\Models\SystemConfiguration;

class ScrapeProduce implements CustomProcessInterface
{
    /**
     * @var bool Quit tag for Reload updates
     */
    private static   $quit = false;
    private static   $producerHandler;
    protected static $config;
    protected static $systemConfiguration;
    protected static $kafkaTopic;
    private static   $scheduleMapping;
    private static   $scheduleMappingField;
    private static   $scheduleType;
    private static   $providers;
    private static   $sports;

    public static function callback(Server $swoole, Process $process)
    {
        $kafkaProducer     = app('KafkaProducer');
        $refreshDBInterval = config('scraping.refreshDBInterval');

        self::$producerHandler      = new ProducerHandler($kafkaProducer);
        self::$kafkaTopic           = env('KAFKA_SCRAPE_REQUEST_POSTFIX', '_req');
        self::$providers            = DB::table('providers')->where('is_enabled', true)->get()->toArray();
        self::$sports               = DB::table('sports')->where('is_enabled', true)->get()->toArray();
        self::$systemConfiguration  = new SystemConfiguration();
        self::$scheduleMapping      = config('scraping.scheduleMapping');
        self::$scheduleMappingField = config('scraping.scheduleMappingField');

        $i = 0;
        while (true) {
            if ($i % $refreshDBInterval == 0) {
                self::refresh_db_config();
            }

            $request = [];
            foreach (self::$scheduleMapping as $key => $scheduleType) {
                foreach (self::$config as $conf) {
                    if (in_array($conf['type'], $scheduleType)) {
                        $request[$key][self::$scheduleMappingField[$conf['type']]] = $conf['value'];
                    }
                }
            }

            foreach ($request as $key => $req) {
                self::$scheduleType = $key;
                if ($i % $req['timer'] == 0) {
                    for ($interval = 0; $interval < $req['requestNumber']; $interval++) {
                        self::sendPayload();
                        usleep($req['requestInterval'] * 1000);
                    }
                }
            }

            $i++;
            sleep(1);
        }
    }

    // Requirements: LaravelS >= v3.4.0 & callback() must be async non-blocking program.
    public static function onReload(Server $swoole, Process $process)
    {
        self::$quit = true;
    }

    private static function sendPayload()
    {
        foreach (self::$providers as $provider) {
            foreach (self::$sports as $sport) {
                $requestId = (string) Str::uuid();
                $requestTs = getMilliseconds();

                $payload         = [
                    'request_uid' => $requestId,
                    'request_ts'  => $requestTs,
                    'sub_command' => 'scrape',
                ];
                $payload['data'] = [
                    'provider' => strtolower($provider->alias),
                    'schedule' => self::$scheduleType,
                    'sport'    => $sport->id
                ];

                // publish message to kafka
                $payload['command'] = 'odd';
                KafkaPush::dispatch(strtolower($provider->alias) . self::$kafkaTopic, $payload, $requestId);
            }
        }
    }

    /**
     * Retrieve update scraping config parameters
     *
     * @return void
     */
    private static function refresh_db_config()
    {
        self::$systemConfiguration->where(true, true);
        foreach (self::$scheduleMapping as $scheduleType) {
            foreach ($scheduleType as $where) {
                self::$systemConfiguration->orWhere('type', $where);
            }
        }
        self::$config = self::$systemConfiguration->select('type', 'value')->get()->toArray();
    }
}
