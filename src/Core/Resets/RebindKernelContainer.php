<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/21
 * Time: 15:38
 */

namespace YSwoole\Core\Resets;

use YSwoole\Core\Sandbox;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Container\Container;
use YSwoole\Core\Resets\ResetContract;

class RebindKernelContainer implements ResetContract
{
    public function handle(Container $app, Sandbox $sandbox)
    {
        // TODO: Implement handle() method.
        if ($sandbox->isLaravel()) {
            $kernel = $app->make(Kernel::class);

            $closure = function () use ($app) {
                $this->app = $app;
            };

            $resetKernel = $closure->bindTo($kernel, $kernel);
            $resetKernel();
        }

        return $app;
    }
}