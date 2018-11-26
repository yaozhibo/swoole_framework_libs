<?php

namespace YSwoole\Core\Resets;

use YSwoole\Core\Sandbox;
use Illuminate\Contracts\Container\Container;
use YSwoole\Core\Resets\ResetContract;

class ResetCookie implements ResetContract
{
    /**
     * "handle" function for resetting app.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     * @param \SwooleTW\Http\Server\Sandbox $sandbox
     */
    public function handle(Container $app, Sandbox $sandbox)
    {
        if (isset($app['cookie'])) {
            $cookies = $app->make('cookie');
            foreach ($cookies->getQueuedCookies() as $key => $value) {
                $cookies->unqueue($key);
            }
        }

        return $app;
    }
}
