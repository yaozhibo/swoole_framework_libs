<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/20
 * Time: 11:39
 */
namespace YSwoole\Providers;

use YSwoole\Core\Manager;
use Illuminate\Support\ServiceProvider;

class LumenServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerManager();
    }

    protected function registerManager()
    {

        $this->app->singleton('yswoole.manager', function ($app) {
            return new Manager($app, 'lumen');
        });
    }
}