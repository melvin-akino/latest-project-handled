<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $connection;

    protected $table = 'providers';

    protected $fillable = [
        'name',
        'alias',
        'punter_percentage',
        'priority',
        'is_enabled'
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

    public static function getActiveProviders()
    {
        return self::where('is_enabled', true)
            ->orderBy('priority', 'asc');
    }
}
