<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PreviousBetExtraction extends Command
{
    /**
     * # Extract transaction/s from previous date
    $ php artisan bets:extract
    # with `DATETIME` option
    $ php artisan bets:extract --dt="2020-09-04"
    // Extract Transactions from 2020-09-04 to present
    # with `STEP` option
    $ php artisan bets:extract --step=3
    // Extract Transaction from 3 days to present
    # with `DATETIME` and `STEP` options
    $ php artisan bets:extract --dt="2020-09-01" --step=2
    // Extract Transactions from 2020-09-01 to 2 days after
     */

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bets:extract {--dt=} {--step=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bet Data Extraction for Previous Date/s.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->line('Running command...');

            $date = is_null($this->option('dt')) ? Carbon::now()->subDay()->format('Y-m-d H:i:') . "00" : $this->option('dt');
//            $this->line('Running command...' . Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('Y-m-d H:i:s'));
            $step = is_null($this->option('step')) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->diffInDays(Carbon::now()) : $this->option('step');
//            $this->line('Running command...' . $step);
            $date = is_null($this->option('dt')) && !is_null($this->option('step')) ? Carbon::now()->subDay($step)->format('Y-m-d H:i:') . "00" : $date;

            $from = Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('Y-m-d H:i:s');
            $to   = Carbon::createFromFormat('Y-m-d H:i:s', $date)->addDays($step)->subSecond()->format('Y-m-d H:i:s');

            if ($date > Carbon::now()->format('Y-m-d H:i:s')) {
                $this->error("ERROR! Invalid 'date' option! Must be on or before current date.");
                return;
            }

            if (Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('Y-m-d H:i:s') == !$date) {
                $this->error("ERROR! Invalid 'date' option! Must be on or before current date.");
                return;
            }

            if (!is_numeric($step)) {
                $this->error("ERROR! Invalid 'step' option! Must be an integer.");
                return;
            }

            if ($to > Carbon::now()->format('Y-m-d H:i:s')) {
                $this->error("ERROR! Target exceeded current date.");
                return;
            }

            $filename = "Extracted_Bet_Transactions_" . Carbon::now()->format('YmdHis') . ".csv";
            $file     = fopen($filename, 'w');
            $columns  = ['id', 'email', 'ml_bet_identifier', 'bet_id', 'username', 'settled_date', 'status', 'stake', 'profit_loss', 'actual_stake', 'actual_profit_loss', 'odds', 'odd_label', 'market_flag', 'odd_type'];
            $dups     = [];
            $data     = DB::table('orders AS o')
                          ->join('provider_accounts AS pa', 'pa.id', '=', 'o.provider_account_id')
                          ->join('users AS u', 'u.id', '=', 'o.user_id')
                          ->join('order_logs AS ol', 'ol.order_id', '=', 'o.id')
                          ->join('provider_account_orders AS pao', 'pao.order_log_id', '=', 'ol.id')
                          ->join('odd_types AS ot', 'ot.id', '=', 'o.odd_type_id')
                          ->where('o.created_at', '>=', $from)
                          ->where('o.created_at', '<=', $to)
                          ->where('o.bet_id', '!=', "")
                          ->orderBy('o.id', 'ASC')
                          ->orderBy('pao.order_log_id', 'DESC')
                          ->distinct()
                          ->get([
                              'o.id',
                              'pao.order_log_id',
                              'u.email',
                              'o.ml_bet_identifier',
                              'o.bet_id',
                              'pa.username',
                              'o.settled_date',
                              'o.status',
                              'o.stake',
                              'o.profit_loss',
                              'pao.actual_stake',
                              'pao.actual_profit_loss',
                              'o.odds',
                              'o.odd_label'
                          ]);

            fputcsv($file, $columns);

            foreach ($data as $row) {
                if (!in_array($row->id, $dups)) {
                    fputcsv($file, [
                        $row->id,
                        $row->email,
                        $row->ml_bet_identifier,
                        $row->bet_id,
                        $row->username,
                        $row->settled_date,
                        $row->status,
                        $row->stake,
                        $row->profit_loss,
                        $row->actual_stake,
                        $row->actual_profit_loss,
                        $row->odds,
                        $row->odd_label
                    ]);

                    $dups[] = $row->id;
                }
            }
            fclose($file);

            $headers = ["Content-type" => "text/csv"];
        } catch (Exception $e) {
            $this->error("ERROR! " . $e->getLine() . " : " . $e->getMessage() . ':' . $e->getTraceAsString());
        }
    }
}
