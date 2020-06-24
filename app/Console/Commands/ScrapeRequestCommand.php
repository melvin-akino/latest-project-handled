<?php

namespace App\Console\Commands;

use App\Models\SystemConfiguration;
use Illuminate\Console\Command;
use App\Handlers\ProducerHandler;
use Exception;
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Support\Str;
use Storage;

class ScrapeRequestCommand extends Command
{
    protected $signature = 'scrape:request';

    protected $description = 'Scraping Request: Odds, Leagues and Events';

    protected $config;

    protected $systemConfiguration;

    /**
     * Topic name
     */
    protected $kafkaTopic;

    /**
     * Kafka producer
     *
     * @var \App\Handlers\Kafka\ProducerHandler
     */
    protected $producerHandler;

    private $scheduleMapping;

    private $scheduleMappingField;

    private $refreshDBInterval;

    public function __construct(ProducerHandler $producerHandler)
    {
        parent::__construct();

        $this->producerHandler = $producerHandler;
        $this->kafkaTopic = env('KAFKA_SCRAPE_REQUEST_POSTFIX', '_req');
    }

    public function handle()
    {
        $this->providers = DB::table('providers')->where('is_enabled', true)->get()->toArray();
        $this->sports = DB::table('sports')->where('is_enabled', true)->get()->toArray();

        $this->systemConfiguration = new SystemConfiguration();

        $this->scheduleMapping = config('scraping.scheduleMapping');

        $this->scheduleMappingField = config('scraping.scheduleMappingField');

        $refreshDBInterval = config('scraping.refreshDBInterval');

        $i = 0;
        while (true) {
            if ($i % $refreshDBInterval == 0) {
                $this->refresh_db_config();
            }

            $request = [];
            foreach ($this->scheduleMapping as $key => $scheduleType) {
                foreach ($this->config as $conf) {
                    if (in_array($conf['type'], $scheduleType)) {
                        $request[$key][$this->scheduleMappingField[$conf['type']]] = $conf['value'];
                    }
                }
            }

            foreach ($request as $key => $req) {
                $this->scheduleType = $key;
                if ($i % $req['timer'] == 0) {
                    for ($interval = 0; $interval < $req['requestNumber']; $interval++) {
                        $this->sendPayload();
                        usleep($req['requestInterval'] * 1000);
                    }
                }
            }

            $i++;
            sleep(1);
        }
    }

    private function sendPayload()
    {
        foreach ($this->providers as $provider) {
            foreach ($this->sports as $sport) {
                $requestId = Str::uuid();
                $requestTs = $this->milliseconds();

                $payload = [
                    'request_uid' => $requestId,
                    'request_ts'  => $requestTs,
                    'sub_command' => 'scrape',
                ];
                $payload['data'] = [
                    'provider' => strtolower($provider->alias),
                    'schedule' => $this->scheduleType,
                    'sport'    => $sport->id
                ];

                // publish message to kafka
                $payload['command'] = 'odd';
                $this->pushToKafka($payload, $requestId, strtolower($provider->alias) . $this->kafkaTopic);
            }
        }
    }

    /**
     * Return microtime in milliseconds
     *
     * @return string
     */
    private function milliseconds()
    {
        $mt = explode(' ', microtime());
        return bcadd($mt[1], $mt[0], 8);
    }

    /**
     * Retrieve update scraping config parameters
     *
     * @return void
     */
    private function refresh_db_config()
    {
        $this->systemConfiguration->where(true, true);
        foreach ($this->scheduleMapping as $scheduleType) {
            foreach ($scheduleType as $where) {
                $this->systemConfiguration->orWhere('type', $where);
            }
        }
        $this->config = $this->systemConfiguration->select('type', 'value')->get()->toArray();
    }

    /**
     * Push command message to kafka
     *
     * @param  array $message
     * @param  string $key
     * @return void
     */
    private function pushToKafka(array $message = [], string $key, string $kafkaTopic)
    {
        try {
            $this->producerHandler->setTopic( $kafkaTopic)
                ->send($message, $key);
        } catch (Exception $e) {
            Log::critical(self::PUBLISH_ERROR_MESSAGE, [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        } finally {
            if (env('CONSUMER_PRODUCER_LOG', false)) {
                Log::channel('kafkaproducelog')->info(json_encode($message));
            }
        }
    }
}
