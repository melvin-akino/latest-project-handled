<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableMultiSoccerScraped extends Migration
{
    protected $tablename  = "multi_soccer_scraped";
    protected $strings    = [
        'running_time' => 20,
        'ft_1x2_id'    => 50,
        'ft_hdp_id'    => 50,
        'ft_hdp_1'     => 10,
        'ft_ou_id'     => 50,
        'ft_ou_2'      => 10,
        'ft_oe_id'     => 50,
        'ht_1x2_id'    => 50,
        'ht_hdp_id'    => 50,
        'ht_hdp_1'     => 10,
        'ht_ou_id'     => 50,
        'ht_ou_2'      => 10,
    ];
    protected $characters = [
        'ft_ou_1',
        'ft_oe_1',
        'ht_ou_1',
    ];
    protected $smalls     = [
        'pos',
        'active',
    ];
    protected $integers   = [
        'uid',
        'provider_id',
        'home_redcard',
        'away_redcard',
        'home_score',
        'away_score',
        'unique_req_id',
    ];
    protected $doubles    = [
        'ft_1x2',
        'ft_hdp_2',
        'ft_ou_3',
        'ft_oe_2',
        'ht_1x2',
        'ht_hdp_2',
        'ht_ou_3',
    ];
    protected $datetimes  = [
        'ref_schedule',
        'unique_req_timestamp',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tablename, function (Blueprint $table) {
            $table->bigIncrements('id');

            foreach ($this->strings AS $string => $length) {
                $table->string($string, $length);
            }

            foreach ($this->characters AS $char) {
                $table->char($char, 1);
            }

            foreach ($this->smalls AS $small) {
                $table->smallInteger($small);
            }

            foreach ($this->integers AS $integer) {
                $table->integer($integer);
            }

            foreach ($this->doubles AS $double) {
                $table->double($double, 2, 3);
            }

            foreach ($this->datetimes AS $datetime) {
                $table->dateTimeTz($datetime);
            }

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tablename);
    }
}
