<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    protected $table = 'system_configurations';

    protected $fillable = [
        'type',
        'value',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->setConnection(config('database.crm_default', 'pgsql_crm'));
    }
}
