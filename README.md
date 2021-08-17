Service requirements
- PHP Swoole
- Redis
- Nginx
- Websocket

<hr />

.env variables
```
APP_NAME=
APP_ENV=
APP_KEY=
APP_DEBUG=
APP_URL=
API_URL=
MIX_API_URL="${API_URL}"
MIX_WEBSOCKET_URL=
MIX_APP_URL=

WALLET_URL=http://wallet
WALLET_CLIENT_ID=
WALLET_CLIENT_SECRET=

LARAVELS_LISTEN_IP=0.0.0.0
LARAVELS_LISTEN_PORT=1215
LARAVELS_WEBSOCKET=true
LARAVELS_TASK_WORKER_NUM=32
LARAVELS_TASK_MAX_REQUEST=0
LARAVELS_KAFKA_CONSUME=true
LARAVELS_KAFKA_PRODUCE=true
LARAVELS_KAFKA_SCRAPE_PRODUCE=false
LARAVELS_TIMER=true
LARAVELS_HANDLE_STATIC=true
SWT_MAX_SIZE=100000

DB_CONNECTION=pgsql
#(change this IP to your own local IP machine)
DB_HOST=db
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

MAIL_DRIVER=smtp
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=

PASSPORT_TOKEN='Multiline Authentication Token'

KAFKA_BROKERS=kafka:9092
KAFKA_DEBUG=false
KAFKA_GROUP_ID=ml
KAFKA_SCRAPE_REQUEST_POSTFIX=_req
KAFKA_SCRAPE_ODDS=SCRAPING-ODDS
KAFKA_SCRAPE_LEAGUES=SCRAPING-PROVIDER-LEAGUES
KAFKA_SCRAPE_EVENTS=SCRAPING-PROVIDER-EVENTS
KAFKA_SCRAPE_MINMAX_REQUEST_POSTFIX=_minmax_req
KAFKA_SCRAPE_MINMAX_ODDS=MINMAX-ODDS
KAFKA_SCRAPE_ORDER_REQUEST_POSTFIX=_bet_req
KAFKA_BET_PLACED=PLACED-BET
KAFKA_SCRAPE_OPEN_ORDERS_POSTFIX=_openorder_req
KAFKA_SCRAPE_OPEN_ORDERS=OPEN-ORDERS
KAFKA_SCRAPE_SETTLEMENT_POSTFIX=_settlement_req
KAFKA_SCRAPE_SETTLEMENTS=SCRAPING-SETTLEMENTS
KAFKA_SCRAPE_BALANCE_POSTFIX=_balance_req
KAFKA_SCRAPE_BALANCE=BALANCE
KAFKA_SCRAPE_MAINTENANCE=PROVIDER-MAINTENANCE
KAFKA_SIDEBAR_LEAGUES=SIDEBAR-LEAGUES
KAFKA_SOCKET=SOCKET-DATA
KAFKA_LOG=false

REDIS_PORT=
REDIS_CLIENT=
REDIS_CLUSTER=
REDIS_HOST=
REDIS_DB=0
REDIS_CACHE_DB=0
QUEUE_CONNECTION=sync

PROMETHEUS_NAMESPACE=
PROMETHEUS_METRICS_ROUTE_ENABLED=
PROMETHEUS_METRICS_ROUTE_MIDDLEWARE=
PROMETHEUS_STORAGE_ADAPTER=
PROMETHEUS_EXPIRE=

REDIS_TOOL_PLACED_BET=
REDIS_TOOL_PLACED_BET_EXPIRE=
REDIS_TOOL_MINMAX=
REDIS_TOOL_MINMAX_EXPIRE=
REDIS_TOOL_SCRAPE_ODDS=
REDIS_TOOL_SCRAPE_EXPIRE=120
KAFKA_MON_TOOL_GROUP_NAME=
DEBUGGING_URL=
MIX_DEBUGGING_URL="${DEBUGGING_URL}"
CONSUMER_PRODUCER_LOG=false
BLOCKED_LINE_REASONS=

```

Pre run
```
$ composer install
$ npm install
$ npm run prod

```

Run service
```
$ php bin/laravels start -d
```

Stop service
```
$ php bin/laravels stop
```
