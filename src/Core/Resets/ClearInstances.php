<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/21
 * Time: 15:34
 */
namespace YSwoole\Core\Resets;

use YSwoole\Core\Sandbox;
use Illuminate\Contracts\Container\Container;
use YSwoole\Core\Resets\ResetContract;

class ClearInstances implements ResetContract
{
    public function handle(Container $app, Sandbox $sandbox)
    {
        // TODO: Implement handle() method.
        $instances = $sandbox->getConfig()->get('yswoole_http.instances', []);

        foreach ($instances as $instance) {
            $app->forgetInstance($instance);
        }

        return $app;
    }
}