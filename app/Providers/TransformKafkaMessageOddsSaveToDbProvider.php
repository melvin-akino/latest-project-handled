<?php
// app/Providers/ToolsServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\TransformKafkatoDb\TransformKafkaMessageOddsSaveToDb;

class TransformKafkaMessageOddsSaveToDbProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('TransformKafkaMessageOddsSaveToDb', function () {
            return new TransformKafkaMessageOddsSaveToDb;
        });
    }
}