<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/21
 * Time: 15:30
 */
namespace YSwoole\Core\Resets;

use Illuminate\Http\Request;
use YSwoole\Core\Sandbox;
use Illuminate\Contracts\Container\Container;
use YSwoole\Core\Resets\ResetContract;

class BindRequest implements ResetContract
{
    public function handle(Container $app, Sandbox $sandbox)
    {
        // TODO: Implement handle() method.
        $request = $sandbox->getRequest();

        if ($request instanceof Request)
        {
            $app->instance('request', $request);
        }

        return $app;
    }
}